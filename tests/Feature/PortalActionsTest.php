<?php

namespace Tests\Feature;

use App\Models\Dispute;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\PackageQuota;
use App\Models\Role;
use App\Models\ScheduleSlot;
use App\Models\Subject;
use App\Models\TentorProfile;
use App\Models\TentorSkill;
use App\Models\TutoringSession;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
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
            'scheduled_at' => now()->subMinute(),
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
            'method' => 'bank_transfer',
            'transaction_id' => 'TX-PORTAL-001',
        ])->assertRedirect();
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
        $this->assertDatabaseHas('student_package_entitlements', [
            'invoice_id' => $invoice->id,
            'user_id' => $student->id,
            'total_sessions' => 4,
            'used_sessions' => 0,
            'remaining_sessions' => 4,
            'status' => 'ACTIVE',
        ]);

        PackageQuota::create([
            'package_id' => $package->id,
            'quota' => 1,
            'used_quota' => 0,
            'is_active' => true,
        ]);

        $tentorProfile = TentorProfile::create([
            'user_id' => $teacher->id,
            'is_verified' => true,
        ]);

        TentorSkill::create([
            'tentor_profile_id' => $tentorProfile->id,
            'subject_id' => $subject->id,
            'hourly_rate' => 50000,
            'is_verified' => true,
        ]);

        $bookingInvoice = Invoice::create([
            'invoice_number' => 'INV-PORTAL-002',
            'user_id' => $student->id,
            'total_amount' => 120000,
            'status' => 'unpaid',
            'issue_date' => now(),
            'due_date' => now()->addDay(),
            'notes' => "Draft invoice package #{$package->id} {$package->name}",
        ]);

        $this->actingAs($student)->post(route('ops.payment.success'), [
            'invoice_id' => $bookingInvoice->id,
            'amount' => 120000,
            'method' => 'bank_transfer',
            'transaction_id' => 'TX-PORTAL-002',
        ])->assertRedirect();

        $slot = ScheduleSlot::create([
            'subject_id' => $subject->id,
            'student_id' => $student->id,
            'tentor_id' => $teacher->id,
            'start_at' => now()->startOfDay()->setTime(19, 0),
            'end_at' => now()->startOfDay()->setTime(20, 30),
            'status' => 'OPEN',
        ]);

        $this->actingAs($student)->post(route('ops.slot.book'), [
            'invoice_id' => $bookingInvoice->id,
            'subject_id' => $subject->id,
            'booking_days' => [now()->addDay()->dayOfWeek],
            'slot_ids' => [$slot->id],
            'delivery_mode' => 'online',
        ])->assertRedirect();

        $this->assertDatabaseHas('student_package_entitlements', [
            'invoice_id' => $bookingInvoice->id,
            'used_sessions' => 4,
            'remaining_sessions' => 0,
            'status' => 'EXHAUSTED',
        ]);
        $this->actingAs($admin)->delete(route('admin.sessions.delete', $slot->id))
            ->assertSessionHasErrors('status');

        $futureSession = TutoringSession::create([
            'student_id' => $student->id,
            'tentor_id' => $teacher->id,
            'subject_id' => $subject->id,
            'scheduled_at' => now()->addHours(10),
            'duration_minutes' => 90,
            'status' => 'booked',
        ]);

        $rescheduleSlot = ScheduleSlot::create([
            'subject_id' => $subject->id,
            'student_id' => $student->id,
            'tentor_id' => $teacher->id,
            'start_at' => now()->startOfDay()->setTime(21, 0),
            'end_at' => now()->startOfDay()->setTime(22, 30),
            'status' => 'OPEN',
        ]);

        $this->actingAs($student)->post(route('ops.reschedule.request'), [
            'tutoring_session_id' => $futureSession->id,
            'booking_day' => now()->addDay()->dayOfWeek,
            'schedule_slot_id' => $rescheduleSlot->id,
            'reason' => 'Ada keperluan',
        ])->assertRedirect();
        $this->assertDatabaseHas('reschedule_requests', [
            'tutoring_session_id' => $futureSession->id,
            'status' => 'PENDING',
        ]);

        $this->actingAs($student)->post(route('ops.reschedule.request'), [
            'tutoring_session_id' => $futureSession->id,
            'booking_day' => now()->addDays(2)->dayOfWeek,
            'schedule_slot_id' => $rescheduleSlot->id,
            'reason' => 'Coba lagi',
        ])->assertSessionHasErrors('tutoring_session_id');

        $nearSession = TutoringSession::create([
            'student_id' => $student->id,
            'tentor_id' => $teacher->id,
            'subject_id' => $subject->id,
            'scheduled_at' => now()->addHours(3),
            'duration_minutes' => 90,
            'status' => 'booked',
        ]);

        $this->actingAs($student)->post(route('ops.reschedule.request'), [
            'tutoring_session_id' => $nearSession->id,
            'booking_day' => now()->addDay()->dayOfWeek,
            'schedule_slot_id' => $rescheduleSlot->id,
            'reason' => 'Terlalu mepet',
        ])->assertSessionHasErrors('tutoring_session_id');

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
        $session->update(['status' => 'completed']);

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
        $this->actingAs($admin)->postJson(route('ops.session.reminder', $session->id))->assertOk();
        $this->actingAs($admin)->post(route('ops.payout.create'), [
            'session_id' => $session->id,
            'net_amount' => 50000,
        ])->assertRedirect();
        $this->assertDatabaseHas('teacher_payouts', [
            'teacher_id' => $teacher->id,
            'tutoring_session_id' => $session->id,
            'status' => 'PENDING',
        ]);

        $wallet = Wallet::create([
            'user_id' => $teacher->id,
            'balance' => 200000,
            'held_balance' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($teacher)->post(route('tutor.wallet.withdraw'), [
            'amount' => 50000,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Tentor Example',
        ])->assertRedirect();
        $withdrawal = Withdrawal::query()->where('wallet_id', $wallet->id)->latest('id')->firstOrFail();
        $this->assertDatabaseHas('withdrawals', [
            'id' => $withdrawal->id,
            'status' => 'requested',
        ]);
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 150000,
        ]);

        $this->actingAs($admin)->post(route('admin.withdrawals.approve', $withdrawal->id), [
            'admin_note' => 'Valid',
        ])->assertRedirect();
        $this->assertDatabaseHas('withdrawals', [
            'id' => $withdrawal->id,
            'status' => 'processing',
        ]);

        $this->actingAs($admin)->post(route('admin.withdrawals.paid', $withdrawal->id), [
            'admin_note' => 'Transferred',
        ])->assertRedirect();
        $this->assertDatabaseHas('withdrawals', [
            'id' => $withdrawal->id,
            'status' => 'completed',
        ]);

        $this->actingAs($teacher)->post(route('tutor.wallet.withdraw'), [
            'amount' => 40000,
            'bank_name' => 'BRI',
            'account_number' => '9876543210',
            'account_holder' => 'Tentor Example',
        ])->assertRedirect();
        $rejectedWithdrawal = Withdrawal::query()->where('wallet_id', $wallet->id)->latest('id')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.withdrawals.reject', $rejectedWithdrawal->id), [
            'admin_note' => 'Data rekening tidak valid',
        ])->assertRedirect();
        $this->assertDatabaseHas('withdrawals', [
            'id' => $rejectedWithdrawal->id,
            'status' => 'rejected',
        ]);
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 150000,
        ]);

        $voidInvoice = Invoice::create([
            'invoice_number' => 'INV-PORTAL-VOID-001',
            'user_id' => $student->id,
            'total_amount' => 50000,
            'status' => 'unpaid',
            'issue_date' => now(),
            'due_date' => now()->addDay(),
        ]);

        $this->actingAs($admin)->delete(route('admin.invoices.delete', $voidInvoice->id), [
            'reason' => 'Tes void invoice',
        ])->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'id' => $voidInvoice->id,
            'status' => 'cancelled',
        ]);
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
