<?php

namespace Tests\Feature;

use App\Jobs\SendRawEmailJob;
use App\Models\PasswordResetRequest;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_registration_is_active_by_default(): void
    {
        Bus::fake();
        $this->ensureRoles(['siswa', 'tentor', 'orang_tua']);

        $response = $this
            ->withSession(['captcha_result' => 7])
            ->post(route('register.post'), [
                'name' => 'Siswa Baru',
                'email' => 'siswa-baru@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'siswa',
                'terms' => '1',
                'captcha' => 7,
                'connection_status' => 'offline',
            ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'email' => 'siswa-baru@example.com',
            'is_active' => true,
        ]);
        Bus::assertDispatchedSync(SendRawEmailJob::class);
    }

    public function test_parent_registration_is_active_by_default(): void
    {
        Bus::fake();
        $this->ensureRoles(['siswa', 'tentor', 'orang_tua']);

        $response = $this
            ->withSession(['captcha_result' => 9])
            ->post(route('register.post'), [
                'name' => 'Orang Tua Baru',
                'email' => 'ortu-baru@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'orang_tua',
                'terms' => '1',
                'captcha' => 9,
                'connection_status' => 'offline',
            ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'email' => 'ortu-baru@example.com',
            'is_active' => true,
        ]);
        Bus::assertDispatchedSync(SendRawEmailJob::class);
    }

    public function test_tentor_registration_stays_inactive_by_default(): void
    {
        Bus::fake();
        $this->ensureRoles(['siswa', 'tentor', 'orang_tua']);
        $subject = Subject::query()->create([
            'name' => 'Matematika',
            'level' => 'SMP',
            'description' => 'Dasar',
            'is_active' => true,
        ]);

        $response = $this
            ->withSession(['captcha_result' => 11])
            ->post(route('register.post'), [
                'name' => 'Tentor Baru',
                'email' => 'tentor-baru@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'tentor',
                'terms' => '1',
                'captcha' => 11,
                'connection_status' => 'offline',
                'phone' => '08123456789',
                'education' => 'S1 Pendidikan',
                'experience_years' => 3,
                'domicile' => 'Jakarta',
                'teaching_mode' => 'online',
                'teaching_subject_ids' => [$subject->id],
            ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'email' => 'tentor-baru@example.com',
            'is_active' => false,
        ]);
        $this->assertSame('PENDING_REVIEW', User::query()->where('email', 'tentor-baru@example.com')->firstOrFail()->tentorProfile->verification_status);
        Bus::assertDispatchedSync(SendRawEmailJob::class);
    }

    public function test_unverified_user_can_resend_verification_email_from_login_flow(): void
    {
        Bus::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'belum-verif@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this
            ->withSession(['captcha_result' => 8])
            ->post(route('login.post'), [
                'email' => $user->email,
                'password' => 'password123',
                'captcha' => 8,
                'connection_status' => 'offline',
            ]);

        $response->assertSessionHas('show_resend_verification', true);

        $resendResponse = $this->post(route('register.resend-verification'), [
            'email' => $user->email,
        ]);

        $resendResponse->assertSessionHas('status', 'Link verifikasi berhasil dikirim ulang.');
        $this->assertDatabaseHas('registration_email_verifications', [
            'email' => $user->email,
        ]);
        Bus::assertDispatchedSync(SendRawEmailJob::class);
    }

    public function test_forgot_password_otp_can_be_resent_without_creating_new_request(): void
    {
        Bus::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        $resetRequest = PasswordResetRequest::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'phone' => '08123456789',
            'channel' => 'EMAIL',
            'otp_code' => '111111',
            'expires_at' => now()->addMinutes(5),
            'request_ip' => '127.0.0.1',
        ]);

        $response = $this->post(route('password.forgot.resend', $resetRequest->id));

        $response->assertSessionHas('status', 'Kode OTP berhasil dikirim ulang via email.');
        $this->assertDatabaseCount('password_reset_requests', 1);
        $this->assertDatabaseMissing('password_reset_requests', [
            'id' => $resetRequest->id,
            'otp_code' => '111111',
        ]);
        Bus::assertDispatchedSync(SendRawEmailJob::class);
    }

    private function ensureRoles(array $roles): void
    {
        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }
    }
}
