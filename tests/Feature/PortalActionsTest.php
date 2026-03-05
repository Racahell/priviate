<?php

namespace Tests\Feature;

use App\Models\Dispute;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\Role;
use App\Models\Subject;
use App\Models\TutoringSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_buttons_actions_work(): void
    {
        $this->ensureRoles(['siswa', 'tentor', 'admin']);

        $student = User::factory()->create([
            'email' => 'siswa@example.com',
            'is_active' => true,
        ]);
        $student->assignRole('siswa');

        $teacher = User::factory()->create([
            'email' => 'tentor@example.com',
            'is_active' => true,
        ]);
        $teacher->assignRole('tentor');

        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $subject = Subject::create([
            'name' => 'Matematika',
            'level' => 'SMA',
            'description' => 'Dasar',
            'is_active' => true,
        ]);

        $package = Package::create([
            'name' => 'Paket A',
            'description' => 'Paket uji',
            'is_active' => true,
            'trial_enabled' => false,
            'trial_limit' => 0,
        ]);

        PackagePrice::create([
            'package_id' => $package->id,
            'price' => 120000,
            'is_active' => true,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-PORTAL-001',
            'user_id' => $student->id,
            'total_amount' => 100000,
            'status' => 'unpaid',
            'issue_date' => now(),
            'due_date' => now()->addDay(),
        ]);

        $session = TutoringSession::create([
            'student_id' => $student->id,
            'tentor_id' => $teacher->id,
            'subject_id' => $subject->id,
            'invoice_id' => $invoice->id,
            'scheduled_at' => now()->addHour(),
            'duration_minutes' => 90,
            'status' => 'booked',
        ]);

        $dispute = Dispute::create([
            'tutoring_session_id' => $session->id,
            'created_by' => $student->id,
            'source_role' => 'siswa',
            'reason' => 'NO_SHOW',
            'description' => 'Guru terlambat',
            'status' => 'DISPUTE_OPEN',
        ]);

        // Student buttons
        $this->actingAs($student)->get(route('student.booking'))->assertOk();
        $this->actingAs($student)->post(route('ops.package.select'), [
            'package_id' => $package->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('invoices', ['user_id' => $student->id, 'status' => 'unpaid']);

        $this->actingAs($student)->get(route('student.invoices'))->assertOk();
        $this->actingAs($student)->post(route('ops.payment.success'), [
            'invoice_id' => $invoice->id,
            'amount' => 100000,
            'method' => 'manual_transfer',
            'transaction_id' => 'TX-PORTAL-001',
        ])->assertRedirect();
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);

        // Tutor buttons
        $this->actingAs($teacher)->get(route('tutor.schedule'))->assertOk();
        $this->actingAs($teacher)->post(route('ops.session.start', $session->id))->assertRedirect();
        $this->assertDatabaseHas('tutoring_sessions', ['id' => $session->id, 'status' => 'ongoing']);

        $this->actingAs($teacher)->post(route('ops.attendance.mark', $session->id), [
            'student_present' => 1,
            'location_status' => 'DENIED',
        ])->assertRedirect();
        $this->assertDatabaseHas('attendance_records', ['tutoring_session_id' => $session->id]);

        $this->actingAs($teacher)->post(route('ops.material.submit', $session->id), [
            'summary' => 'Materi fungsi kuadrat',
        ])->assertRedirect();
        $this->assertDatabaseHas('material_reports', ['tutoring_session_id' => $session->id]);

        // Admin buttons
        $this->actingAs($admin)->get(route('admin.disputes'))->assertOk();
        $this->actingAs($admin)->put(route('ops.dispute.update', $dispute->id), [
            'status' => 'IN_REVIEW_ADMIN',
            'notes' => 'Sedang ditinjau',
        ])->assertRedirect();
        $this->assertDatabaseHas('disputes', ['id' => $dispute->id, 'status' => 'IN_REVIEW_ADMIN']);

        $this->actingAs($admin)->post(route('ops.dispute.resolve', $dispute->id), [
            'notes' => 'Diselesaikan',
        ])->assertRedirect();
        $this->assertDatabaseHas('disputes', ['id' => $dispute->id, 'status' => 'RESOLVED']);

        $this->actingAs($admin)->get(route('admin.monitor'))->assertOk();
        $this->actingAs($admin)->post(route('ops.session.reminder', $session->id))->assertOk();
        $this->actingAs($admin)->post(route('ops.payout.create'), [
            'teacher_id' => $teacher->id,
            'net_amount' => 50000,
        ])->assertRedirect();
        $this->assertDatabaseHas('teacher_payouts', ['teacher_id' => $teacher->id, 'status' => 'PENDING']);
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
