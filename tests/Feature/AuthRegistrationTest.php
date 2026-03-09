<?php

namespace Tests\Feature;

use App\Jobs\SendRawEmailJob;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_registration_is_active_by_default(): void
    {
        Queue::fake();
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
        Queue::assertPushed(SendRawEmailJob::class);
    }

    public function test_parent_registration_is_active_by_default(): void
    {
        Queue::fake();
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
        Queue::assertPushed(SendRawEmailJob::class);
    }

    public function test_tentor_registration_stays_inactive_by_default(): void
    {
        Queue::fake();
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
        Queue::assertPushed(SendRawEmailJob::class);
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
