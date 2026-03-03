<?php

namespace App\Http\Controllers;

use App\Jobs\SendRawEmailJob;
use App\Jobs\SendWhatsappMessageJob;
use App\Models\LoginEvent;
use App\Models\PasswordResetRequest;
use App\Models\RegistrationEmailVerification;
use App\Models\SecurityEvent;
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
        return view('auth.preverify');
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

        SendRawEmailJob::dispatch(
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
            return redirect()->route('register.preverify')->withErrors([
                'email' => 'Link aktivasi tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        $verification->update(['used_at' => now()]);
        $request->session()->put('pre_verified_email', $email);

        $this->auditService->log('EMAIL_PREVERIFY_SUCCESS', null, [], ['email' => $email]);

        return redirect()->route('register')->with('status', 'Email berhasil diverifikasi. Silakan lanjut daftar.');
    }

    public function showRegisterForm(Request $request)
    {
        $preVerifiedEmail = $request->session()->get('pre_verified_email');
        if (!$preVerifiedEmail) {
            return redirect()->route('register.preverify')->withErrors([
                'email' => 'Silakan verifikasi email terlebih dahulu.',
            ]);
        }

        $captchaQuestion = $this->captchaService->generate();
        $recaptchaSiteKey = config('services.recaptcha.site_key');

        return view('auth.register', compact('captchaQuestion', 'preVerifiedEmail', 'recaptchaSiteKey'));
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
        $role = $user->getRoleNames()->first();
        $isHighRole = in_array($role, ['superadmin', 'owner', 'admin'], true);
        $anomalyFlag = $isHighRole && !empty($user->last_login_ip) && $user->last_login_ip !== $request->ip();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
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

        if (in_array($role, ['superadmin', 'owner'], true)) {
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
        return view('auth.forgot-password');
    }

    public function sendForgotPasswordOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'channel' => 'required|in:EMAIL,WHATSAPP',
        ]);

        $user = User::where('email', strtolower($request->email))->first();
        if (!$user) {
            return back()->withErrors(['email' => 'Email tidak ditemukan.']);
        }

        $otp = (string) random_int(100000, 999999);
        $record = PasswordResetRequest::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'phone' => $user->phone,
            'channel' => $request->channel,
            'otp_code' => $otp,
            'expires_at' => now()->addMinutes(15),
            'request_ip' => $request->ip(),
        ]);

        if ($request->channel === 'EMAIL') {
            SendRawEmailJob::dispatch(
                $user->email,
                'Kode Reset Password',
                "Kode reset password Anda: {$otp}. Berlaku 15 menit."
            );
        } else {
            $this->sendWhatsappOtp($user->phone, $otp);
        }

        $this->auditService->log('PASSWORD_RESET_REQUESTED', $user, [], [
            'channel' => $request->channel,
        ]);

        return redirect()->route('password.forgot.verify', ['requestId' => $record->id])
            ->with('status', 'Kode OTP sudah dikirim.');
    }

    public function showForgotPasswordVerifyForm(int $requestId)
    {
        return view('auth.forgot-password-verify', compact('requestId'));
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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:siswa,tentor',
            'terms' => 'required|accepted',
        ]);

        $preVerifiedEmail = strtolower((string) $request->session()->get('pre_verified_email'));
        if (strtolower($request->email) !== $preVerifiedEmail) {
            return back()->withErrors([
                'email' => 'Email harus sesuai email yang sudah diverifikasi.',
            ])->withInput();
        }

        if (!$this->captchaService->verify($request)) {
            return back()->withErrors(['captcha' => 'Verifikasi captcha gagal.'])->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
            'created_ip' => $request->ip(),
        ]);

        $user->assignRole($request->role);

        if (Schema::hasTable('user_consents')) {
            UserConsent::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'tos_version' => 'v1.0',
                'agreed_at' => now(),
            ]);
        }

        $this->auditService->log('REGISTER', $user, [], $user->toArray(), $this->requestContext($request, false));

        $request->session()->forget('pre_verified_email');
        Auth::login($user);

        return $this->redirectByRole($user);
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

        $user = $request->user();
        if ($user) {
            $user->update([
                'latitude' => $request->location_status === 'ALLOW' ? $request->latitude : null,
                'longitude' => $request->location_status === 'ALLOW' ? $request->longitude : null,
            ]);
        }

        $this->auditService->log('LOCATION_PERMISSION', $user, [], [
            'location_status' => $request->location_status,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ], $this->requestContext($request, false));

        return response()->json(['ok' => true]);
    }

    private function initiateTwoFactor(Request $request, User $user)
    {
        $code = (string) random_int(100000, 999999);
        cache()->put("login_2fa_{$user->id}", $code, now()->addMinutes(5));
        $request->session()->put('pending_2fa_user_id', $user->id);

        Auth::logout();
        SendRawEmailJob::dispatch(
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
        if ($user->hasRole('siswa')) {
            return redirect()->route('student.dashboard');
        }
        if ($user->hasRole('tentor')) {
            return redirect()->route('tutor.dashboard');
        }
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->hasRole('manager')) {
            return redirect()->route('manager.dashboard');
        }
        if ($user->hasRole('owner')) {
            return redirect()->route('owner.dashboard');
        }
        if ($user->hasRole('superadmin')) {
            return redirect()->route('superadmin.dashboard');
        }
        if ($user->hasRole('orang_tua')) {
            return redirect()->route('parent.dashboard');
        }

        return redirect('/');
    }

    private function logLogin(?User $user, string $status, Request $request, bool $anomalyFlag, array $metadata = []): void
    {
        if (!Schema::hasTable('login_events')) {
            return;
        }

        LoginEvent::create([
            'user_id' => $user?->id,
            'role' => $user?->getRoleNames()->first(),
            'status' => $status,
            'session_id' => $request->session()->getId(),
            'ip_address' => $request->ip(),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'location_status' => $request->input('location_status', 'DENIED'),
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

    private function requestContext(Request $request, bool $anomalyFlag): array
    {
        return [
            'location_status' => $request->input('location_status', 'DENIED'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'device_fingerprint' => $request->header('X-Device-Fingerprint'),
            'browser' => $request->header('X-Browser'),
            'os' => $request->header('X-OS'),
            'anomaly_flag' => $anomalyFlag,
        ];
    }
}
