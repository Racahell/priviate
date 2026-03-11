<?php

namespace App\Http\Controllers;

use App\Jobs\SendRawEmailJob;
use App\Jobs\SendWhatsappMessageJob;
use App\Models\LoginEvent;
use App\Models\PasswordResetRequest;
use App\Models\RegistrationEmailVerification;
use App\Models\SecurityEvent;
use App\Models\Subject;
use App\Models\TentorProfile;
use App\Models\TentorSkill;
use App\Models\User;
use App\Models\UserConsent;
use App\Services\AuditService;
use App\Services\CaptchaService;
use App\Services\DiscordAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private readonly CaptchaService $captchaService,
        private readonly AuditService $auditService,
        private readonly DiscordAlertService $discordAlertService
    ) {
    }

    public function showLoginForm()
    {
        $captchaQuestion = $this->captchaService->generate();
        $recaptchaSiteKey = config('services.recaptcha.site_key');

        return view('auth.login', compact('captchaQuestion', 'recaptchaSiteKey'));
    }

    public function showPreVerifyForm()
    {
        return redirect()->route('register');
    }

    public function sendPreVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        if (User::where('email', $request->email)->exists()) {
            return back()->withErrors(['email' => 'Email sudah terdaftar. Silakan login.']);
        }

        $token = hash('sha256', Str::uuid()->toString() . '|' . $request->email . '|' . now()->timestamp);
        RegistrationEmailVerification::create([
            'email' => strtolower($request->email),
            'token' => $token,
            'expires_at' => now()->addMinutes(30),
            'sent_ip' => $request->ip(),
        ]);

        $activationLink = route('register.activate', ['token' => $token, 'email' => strtolower($request->email)]);

        SendRawEmailJob::dispatchSync(
            strtolower($request->email),
            'Aktivasi Registrasi Akun',
            "Klik link aktivasi berikut untuk lanjut registrasi:\n{$activationLink}\n\nLink berlaku 30 menit."
        );

        $this->auditService->log('EMAIL_PREVERIFY_SENT', null, [], [
            'email' => strtolower($request->email),
        ]);

        return back()->with('status', 'Link aktivasi sudah dikirim ke email Anda.');
    }

    public function activatePreVerification(Request $request, string $token)
    {
        $email = strtolower((string) $request->query('email'));

        $verification = RegistrationEmailVerification::where('email', $email)
            ->where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>=', now())
            ->latest('id')
            ->first();

        if (!$verification) {
            return redirect()->route('register')->withErrors([
                'email' => 'Link verifikasi tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('register')->withErrors([
                'email' => 'Data user untuk email ini tidak ditemukan. Silakan daftar ulang.',
            ]);
        }

        $verification->update(['used_at' => now()]);
        if (!$user->email_verified_at) {
            $isTentor = $user->hasRole('tentor');
            $canActivate = true;
            if ($isTentor) {
                $profileStatus = (string) (TentorProfile::query()
                    ->where('user_id', $user->id)
                    ->value('verification_status') ?? 'PENDING_REVIEW');
                $canActivate = in_array(strtoupper($profileStatus), ['APPROVED', 'VERIFIED'], true);
            }
            $user->forceFill([
                'email_verified_at' => now(),
                'is_active' => $canActivate,
            ])->save();
        }

        $this->auditService->log('EMAIL_VERIFY_SUCCESS', $user, [], ['email' => $email]);

        return redirect()->route('login')->with('status', 'Email berhasil diverifikasi. Silakan login.');
    }

    public function showRegisterForm(Request $request)
    {
        $captchaQuestion = $this->captchaService->generate();
        $recaptchaSiteKey = config('services.recaptcha.site_key');
        $subjects = Subject::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('level')
            ->get(['id', 'name', 'level']);

        return view('auth.register', compact('captchaQuestion', 'recaptchaSiteKey', 'subjects'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = Str::lower($request->email) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        if (!$this->captchaService->verify($request)) {
            RateLimiter::hit($throttleKey, 300);
            $this->logLogin(null, 'LOGIN_FAILED', $request, false, ['reason' => 'captcha_failed']);
            return back()->withErrors(['captcha' => 'Verifikasi captcha gagal.'])->withInput();
        }

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 300);
            $this->logLogin(null, 'LOGIN_FAILED', $request, false, ['reason' => 'invalid_credentials']);
            $this->auditService->log('LOGIN_FAILED');
            return back()->withErrors([
                'email' => 'Email atau password tidak cocok.',
            ])->withInput();
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $user = Auth::user();
        if (!$user->email_verified_at) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors([
                'email' => 'Email belum diverifikasi. Cek inbox untuk link verifikasi.',
            ])->withInput()->with([
                'show_resend_verification' => true,
                'unverified_email' => $user->email,
            ]);
        }
        if (!(bool) $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $inactiveMessage = $user->hasRole('tentor')
                ? 'Akun tentor Anda belum aktif. Menunggu verifikasi admin.'
                : 'Akun Anda belum aktif. Menunggu persetujuan admin.';
            return back()->withErrors([
                'email' => $inactiveMessage,
            ])->withInput();
        }
        $role = $user->getRoleNames()->first();
        $isHighRole = in_array($role, ['superadmin', 'owner', 'admin'], true);
        $anomalyFlag = $isHighRole && !empty($user->last_login_ip) && $user->last_login_ip !== $request->ip();
        $location = $this->normalizeLocationPayload($request);

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
        ])->save();

        $this->logLogin($user, 'LOGIN_SUCCESS', $request, $anomalyFlag);
        $this->auditService->log('LOGIN_SUCCESS', $user, [], [
            'ip' => $request->ip(),
        ], $this->requestContext($request, $anomalyFlag));

        if ($anomalyFlag && $isHighRole) {
            $this->discordAlertService->send('Anomalous High Role Login', [
                'user_id' => $user->id,
                'role' => $role,
                'ip' => $request->ip(),
                'previous_ip' => $user->getOriginal('last_login_ip'),
            ], 'critical');
        }

        $login2faEnabled = (bool) config('services.auth.login_2fa_enabled', false);
        if ($login2faEnabled && in_array($role, ['superadmin', 'owner'], true)) {
            return $this->initiateTwoFactor($request, $user);
        }

        return $this->redirectByRole($user);
    }

    public function showTwoFactorForm(Request $request)
    {
        if (!$request->session()->has('pending_2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.twofactor');
    }

    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|string|size:6',
        ]);

        $pendingUserId = $request->session()->get('pending_2fa_user_id');
        if (!$pendingUserId) {
            return redirect()->route('login')->withErrors(['otp_code' => 'Sesi 2FA tidak ditemukan.']);
        }

        $cacheKey = "login_2fa_{$pendingUserId}";
        $expectedCode = cache()->get($cacheKey);
        if (!$expectedCode || $expectedCode !== $request->otp_code) {
            return back()->withErrors(['otp_code' => 'Kode OTP tidak valid.']);
        }

        cache()->forget($cacheKey);
        $request->session()->forget('pending_2fa_user_id');
        Auth::loginUsingId($pendingUserId);
        $request->session()->regenerate();

        $user = Auth::user();
        $this->auditService->log('2FA_SUCCESS', $user);

        return $this->redirectByRole($user);
    }

    public function showForgotPasswordForm()
    {
        $prefillEmail = auth()->check()
            ? auth()->user()?->email
            : request()->query('email');

        return view('auth.forgot-password', compact('prefillEmail'));
    }

    public function sendForgotPasswordOtp(Request $request)
    {
        $request->validate([
            'email' => 'nullable|email',
            'channel' => 'required|in:EMAIL,WHATSAPP',
        ]);

        $targetEmail = strtolower((string) ($request->email ?: $request->user()?->email));
        if (empty($targetEmail)) {
            return back()->withErrors(['email' => 'Email wajib diisi.']);
        }

        $user = User::where('email', $targetEmail)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'Email tidak ditemukan.']);
        }

        $record = PasswordResetRequest::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'phone' => $user->phone,
            'channel' => $request->channel,
            'otp_code' => (string) random_int(100000, 999999),
            'expires_at' => now()->addMinutes(15),
            'request_ip' => $request->ip(),
        ]);

        $this->dispatchPasswordResetOtp($record, $user);

        $this->auditService->log('PASSWORD_RESET_REQUESTED', $user, [], [
            'channel' => $request->channel,
        ]);

        return redirect()->route('password.forgot.verify', ['requestId' => $record->id])
            ->with('status', 'Kode OTP sudah dikirim.');
    }

    public function showForgotPasswordVerifyForm(int $requestId)
    {
        $resetRequest = PasswordResetRequest::where('id', $requestId)
            ->whereNull('used_at')
            ->where('expires_at', '>=', now())
            ->first();

        if (!$resetRequest) {
            return redirect()->route('password.forgot')->withErrors([
                'email' => 'Permintaan reset password tidak ditemukan atau sudah kedaluwarsa.',
            ]);
        }

        $channel = strtoupper((string) $resetRequest->channel);
        $destinationLabel = $channel === 'EMAIL'
            ? $this->maskEmail((string) $resetRequest->email)
            : $this->maskPhone((string) $resetRequest->phone);

        return view('auth.forgot-password-verify', compact('requestId', 'channel', 'destinationLabel'));
    }

    public function resendVerificationEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower((string) $request->email);
        $throttleKey = 'resend-verification-email:' . $email . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Terlalu banyak permintaan kirim ulang email. Coba lagi dalam {$seconds} detik.",
            ])->withInput();
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->withErrors([
                'email' => 'Email tidak ditemukan.',
            ])->withInput();
        }

        if ($user->email_verified_at) {
            return back()->with('status', 'Email ini sudah terverifikasi. Silakan login.');
        }

        RateLimiter::hit($throttleKey, 300);
        $this->sendRegistrationVerificationEmail($user, $request->ip());
        $this->auditService->log('EMAIL_VERIFICATION_RESENT', $user, [], [
            'email' => $email,
        ]);

        return back()->with('status', 'Link verifikasi berhasil dikirim ulang.');
    }

    public function resendForgotPasswordOtp(Request $request, int $requestId)
    {
        $throttleKey = 'resend-password-reset-otp:' . $requestId . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'otp_code' => "Terlalu banyak permintaan kirim ulang OTP. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        $resetRequest = PasswordResetRequest::where('id', $requestId)
            ->whereNull('used_at')
            ->where('expires_at', '>=', now())
            ->first();

        if (!$resetRequest) {
            return redirect()->route('password.forgot')->withErrors([
                'email' => 'Permintaan reset password tidak ditemukan atau sudah kedaluwarsa.',
            ]);
        }

        $user = User::find($resetRequest->user_id);
        if (!$user) {
            return redirect()->route('password.forgot')->withErrors([
                'email' => 'User untuk permintaan reset password ini tidak ditemukan.',
            ]);
        }

        $resetRequest->forceFill([
            'otp_code' => (string) random_int(100000, 999999),
            'expires_at' => now()->addMinutes(15),
            'request_ip' => $request->ip(),
        ])->save();

        RateLimiter::hit($throttleKey, 300);
        $this->dispatchPasswordResetOtp($resetRequest, $user);
        $this->auditService->log('PASSWORD_RESET_OTP_RESENT', $user, [], [
            'channel' => $resetRequest->channel,
        ]);

        $channelLabel = strtoupper((string) $resetRequest->channel) === 'EMAIL' ? 'email' : 'WhatsApp';

        return back()->with('status', "Kode OTP berhasil dikirim ulang via {$channelLabel}.");
    }

    public function resetForgotPassword(Request $request, int $requestId)
    {
        $request->validate([
            'otp_code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $resetRequest = PasswordResetRequest::where('id', $requestId)
            ->whereNull('used_at')
            ->where('expires_at', '>=', now())
            ->first();

        if (!$resetRequest || $resetRequest->otp_code !== $request->otp_code) {
            return back()->withErrors(['otp_code' => 'OTP tidak valid atau sudah kedaluwarsa.']);
        }

        $user = User::findOrFail($resetRequest->user_id);
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $resetRequest->update(['used_at' => now()]);

        $this->auditService->log('PASSWORD_RESET_COMPLETED', $user);

        return redirect()->route('login')->with('status', 'Password berhasil diubah. Silakan login.');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:siswa,tentor,orang_tua',
            'terms' => 'required|accepted',
            'phone' => 'nullable|string|max:50',
            'education' => 'nullable|string|max:255',
            'experience_years' => 'nullable|integer|min:0|max:60',
            'domicile' => 'nullable|string|max:255',
            'teaching_mode' => 'nullable|in:online,offline,hybrid',
            'offline_coverage' => 'nullable|string|max:255',
            'tentor_bio' => 'nullable|string',
            'teaching_subject_ids' => 'nullable|array',
            'teaching_subject_ids.*' => 'integer|exists:subjects,id',
            'cv_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'diploma_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_card_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'profile_photo_file' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'intro_video_url' => 'nullable|url|max:255',
        ]);

        if ($validated['role'] === 'tentor') {
            $request->validate([
                'phone' => 'required|string|max:50',
                'education' => 'required|string|max:255',
                'experience_years' => 'required|integer|min:0|max:60',
                'domicile' => 'required|string|max:255',
                'teaching_mode' => 'required|in:online,offline,hybrid',
                'teaching_subject_ids' => 'required|array|min:1',
            ]);
        }

        if (!$this->captchaService->verify($request)) {
            return back()->withErrors(['captcha' => 'Verifikasi captcha gagal.'])->withInput();
        }

        $isTentor = $validated['role'] === 'tentor';

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'email_verified_at' => null,
            'is_active' => !$isTentor,
            'created_ip' => $request->ip(),
        ]);

        $user->assignRole($request->role);
        if ($request->role === 'siswa' && empty($user->code)) {
            $user->forceFill(['code' => $this->generateStudentCode()])->save();
        }
        if ($isTentor) {
            $profile = TentorProfile::query()->create([
                'user_id' => $user->id,
                'bio' => $request->tentor_bio,
                'education' => $request->education,
                'experience_years' => $request->experience_years,
                'domicile' => $request->domicile,
                'teaching_mode' => $request->teaching_mode ?? 'online',
                'offline_coverage' => $request->offline_coverage,
                'verification_status' => 'PENDING_REVIEW',
                'is_verified' => false,
                'cv_path' => $this->storeTentorFile($request, 'cv_file', $user->id),
                'diploma_path' => $this->storeTentorFile($request, 'diploma_file', $user->id),
                'certificate_path' => $this->storeTentorFile($request, 'certificate_file', $user->id),
                'id_card_path' => $this->storeTentorFile($request, 'id_card_file', $user->id),
                'profile_photo_path' => $this->storeTentorFile($request, 'profile_photo_file', $user->id),
                'intro_video_url' => $request->intro_video_url,
            ]);

            $subjectIds = collect($request->input('teaching_subject_ids', []))
                ->filter(fn ($v) => is_numeric($v))
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values()
                ->all();

            foreach ($subjectIds as $subjectId) {
                TentorSkill::query()->updateOrCreate(
                    [
                        'tentor_profile_id' => $profile->id,
                        'subject_id' => $subjectId,
                    ],
                    [
                        'hourly_rate' => 0,
                        'is_verified' => false,
                    ]
                );
            }
        }

        if (Schema::hasTable('user_consents')) {
            UserConsent::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'tos_version' => 'v1.0',
                'agreed_at' => now(),
            ]);
        }

        $this->sendRegistrationVerificationEmail($user, $request->ip());

        $this->auditService->log('REGISTER_PENDING_EMAIL_VERIFICATION', $user, [], $user->toArray(), $this->requestContext($request, false));

        return redirect()->route('login')->with('status', 'Registrasi berhasil. Link verifikasi sudah dikirim ke email Anda.');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        $this->auditService->log('LOGOUT', $user);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function updateLocationConsent(Request $request)
    {
        $request->validate([
            'location_status' => 'required|in:ALLOW,DENIED',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);
        $location = $this->normalizeLocationPayload($request);

        $user = $request->user();
        if ($user) {
            $user->update([
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
            ]);
        }

        $this->auditService->log('LOCATION_PERMISSION', $user, [], [
            'location_status' => $location['location_status'],
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
        ], $this->requestContext($request, false));

        return response()->json(['ok' => true]);
    }

    private function initiateTwoFactor(Request $request, User $user)
    {
        $code = (string) random_int(100000, 999999);
        cache()->put("login_2fa_{$user->id}", $code, now()->addMinutes(5));
        $request->session()->put('pending_2fa_user_id', $user->id);

        Auth::logout();
        SendRawEmailJob::dispatchSync(
            $user->email,
            'OTP Login 2FA',
            "Kode OTP login Anda: {$code}. Berlaku 5 menit."
        );

        SecurityEvent::create([
            'user_id' => $user->id,
            'event_type' => '2FA_OTP_SENT',
            'severity' => 'MEDIUM',
            'description' => '2FA OTP sent for high role login.',
            'ip_address' => $request->ip(),
            'metadata' => ['role' => $user->getRoleNames()->first()],
        ]);

        $this->auditService->log('2FA_REQUIRED', $user);

        return redirect()->route('twofactor.form')->with('status', 'Kode OTP sudah dikirim ke email Anda.');
    }

    private function redirectByRole(User $user)
    {
        return redirect()->route('dashboard');
    }

    private function logLogin(?User $user, string $status, Request $request, bool $anomalyFlag, array $metadata = []): void
    {
        if (!Schema::hasTable('login_events')) {
            return;
        }
        $location = $this->normalizeLocationPayload($request);

        LoginEvent::create([
            'user_id' => $user?->id,
            'role' => $user?->getRoleNames()->first(),
            'status' => $status,
            'session_id' => $request->session()->getId(),
            'ip_address' => $request->ip(),
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'location_status' => $location['location_status'],
            'device_fingerprint' => $request->header('X-Device-Fingerprint'),
            'browser' => $request->header('X-Browser'),
            'os' => $request->header('X-OS'),
            'anomaly_flag' => $anomalyFlag,
            'metadata' => $metadata,
        ]);
    }

    private function sendWhatsappOtp(?string $phone, string $otp): void
    {
        if (empty($phone) || empty(config('services.fonnte.token'))) {
            return;
        }

        SendWhatsappMessageJob::dispatch($phone, "Kode reset password Anda: {$otp}. Berlaku 15 menit.");
    }

    private function sendRegistrationVerificationEmail(User $user, ?string $ipAddress = null): void
    {
        $token = hash('sha256', Str::uuid()->toString() . '|' . $user->email . '|' . now()->timestamp);
        RegistrationEmailVerification::create([
            'email' => $user->email,
            'token' => $token,
            'expires_at' => now()->addMinutes(30),
            'sent_ip' => $ipAddress,
        ]);

        $verificationLink = route('register.activate', ['token' => $token, 'email' => $user->email]);
        SendRawEmailJob::dispatchSync(
            $user->email,
            'Verifikasi Email Akun PrivTuition',
            "Halo {$user->name},\n\nKlik link berikut untuk verifikasi email akun Anda:\n{$verificationLink}\n\nLink berlaku 30 menit."
        );
    }

    private function dispatchPasswordResetOtp(PasswordResetRequest $record, User $user): void
    {
        if (strtoupper((string) $record->channel) === 'EMAIL') {
            SendRawEmailJob::dispatchSync(
                $user->email,
                'Kode Reset Password',
                "Kode reset password Anda: {$record->otp_code}. Berlaku 15 menit."
            );

            return;
        }

        $this->sendWhatsappOtp($user->phone, (string) $record->otp_code);
    }

    private function maskEmail(string $email): string
    {
        if (!str_contains($email, '@')) {
            return $email;
        }

        [$localPart, $domain] = explode('@', $email, 2);
        $visibleLocal = substr($localPart, 0, min(2, strlen($localPart)));
        $maskedLocal = $visibleLocal . str_repeat('*', max(strlen($localPart) - strlen($visibleLocal), 0));

        return $maskedLocal . '@' . $domain;
    }

    private function maskPhone(string $phone): string
    {
        $length = strlen($phone);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($phone, 0, 3) . str_repeat('*', max($length - 5, 0)) . substr($phone, -2);
    }

    private function requestContext(Request $request, bool $anomalyFlag): array
    {
        $location = $this->normalizeLocationPayload($request);

        return [
            'location_status' => $location['location_status'],
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'device_fingerprint' => $request->header('X-Device-Fingerprint'),
            'browser' => $request->header('X-Browser'),
            'os' => $request->header('X-OS'),
            'anomaly_flag' => $anomalyFlag,
        ];
    }

    private function normalizeLocationPayload(Request $request): array
    {
        $status = strtoupper((string) $request->input('location_status', 'DENIED'));
        if ($status !== 'ALLOW') {
            return [
                'location_status' => 'DENIED',
                'latitude' => null,
                'longitude' => null,
            ];
        }

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return [
                'location_status' => 'DENIED',
                'latitude' => null,
                'longitude' => null,
            ];
        }

        $latitude = round((float) $latitude, 8);
        $longitude = round((float) $longitude, 8);

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return [
                'location_status' => 'DENIED',
                'latitude' => null,
                'longitude' => null,
            ];
        }

        return [
            'location_status' => 'ALLOW',
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    private function generateStudentCode(): string
    {
        do {
            $candidate = 'SIS-' . strtoupper(Str::random(8));
            $exists = User::where('code', $candidate)->exists();
        } while ($exists);

        return $candidate;
    }

    private function storeTentorFile(Request $request, string $field, int $userId): ?string
    {
        if (!$request->hasFile($field)) {
            return null;
        }

        $file = $request->file($field);
        if (!$file || !$file->isValid()) {
            return null;
        }

        return $file->store("tentor-docs/{$userId}", 'public');
    }
}
