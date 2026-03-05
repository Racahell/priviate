<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Dispute;
use App\Models\DisputeAction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MaterialReport;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\PackageQuota;
use App\Models\Payment;
use App\Models\RescheduleRequest;
use App\Models\ScheduleAssignment;
use App\Models\ScheduleSlot;
use App\Models\TeacherPayout;
use App\Models\TutoringSession;
use App\Services\AuditService;
use App\Services\DiscordAlertService;
use App\Services\EscrowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationsController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly DiscordAlertService $discordAlertService,
        private readonly EscrowService $escrowService
    ) {
    }

    public function selectPackage(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|integer',
        ]);

        $package = Package::findOrFail($validated['package_id']);
        $price = PackagePrice::where('package_id', $package->id)->where('is_active', true)->latest('id')->first();
        $quota = PackageQuota::where('package_id', $package->id)->where('is_active', true)->latest('id')->first();

        if (!$package->is_active || !$price || ($quota && $quota->used_quota >= $quota->quota)) {
            return back()->withErrors(['package_id' => 'Paket tidak tersedia.']);
        }

        $invoice = Invoice::create([
            'invoice_number' => 'DRAFT-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'user_id' => auth()->id(),
            'total_amount' => $price->price,
            'status' => 'unpaid',
            'issue_date' => now(),
            'due_date' => now()->addDays(1),
            'notes' => "Draft invoice package {$package->name}",
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => "Paket: {$package->name} | Snapshot harga saat checkout",
            'quantity' => 1,
            'unit_price' => $price->price,
            'amount' => $price->price,
        ]);

        $this->auditService->log('PACKAGE_SELECTED', $invoice, [], [
            'package_id' => $package->id,
            'price' => $price->price,
        ]);

        return back()->with('status', "Paket dipilih. Draft invoice #{$invoice->invoice_number} dibuat.");
    }

    public function paymentSuccess(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|integer',
            'amount' => 'required|numeric|min:1',
            'transaction_id' => 'nullable|string|max:255',
            'method' => 'required|string|max:64',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method' => $validated['method'],
            'transaction_id' => $validated['transaction_id'] ?? null,
            'amount' => $validated['amount'],
            'status' => 'success',
            'paid_at' => now(),
        ]);

        $invoice->update(['status' => 'paid']);

        $this->auditService->log('PAYMENT_SUCCESS', $payment, [], $payment->toArray());

        return back()->with('status', 'Pembayaran berhasil. Dana masuk escrow hold.');
    }

    public function selectSubject(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|integer',
            'preferred_time' => 'nullable|date',
        ]);

        $this->auditService->log('SUBJECT_SELECTED', null, [], [
            'subject_id' => $validated['subject_id'],
            'preferred_time' => $validated['preferred_time'] ?? null,
        ]);

        return back()->with('status', 'Mapel dan preferensi jadwal disimpan.');
    }

    public function createSchedule(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|integer',
            'tentor_id' => 'required|integer',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
        ]);

        $overlap = ScheduleSlot::where('tentor_id', $validated['tentor_id'])
            ->whereIn('status', ['LOCKED', 'BOOKED'])
            ->where(function ($q) use ($validated) {
                $q->whereBetween('start_at', [$validated['start_at'], $validated['end_at']])
                    ->orWhereBetween('end_at', [$validated['start_at'], $validated['end_at']]);
            })->exists();

        if ($overlap) {
            return back()->withErrors(['start_at' => 'Jadwal bentrok.']);
        }

        $slot = ScheduleSlot::create([
            'subject_id' => $validated['subject_id'],
            'student_id' => auth()->id(),
            'tentor_id' => $validated['tentor_id'],
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'],
            'status' => 'LOCKED',
            'locked_at' => now(),
            'lock_expires_at' => now()->addMinutes(3),
        ]);

        ScheduleAssignment::create([
            'schedule_slot_id' => $slot->id,
            'assigned_by' => auth()->id(),
            'tentor_id' => $validated['tentor_id'],
            'assignment_mode' => 'MANUAL',
        ]);

        $this->auditService->log('SCHEDULE_CREATED', $slot);

        return back()->with('status', 'Jadwal berhasil dibuat dan dikunci.');
    }

    public function sendReminder(int $sessionId)
    {
        $session = TutoringSession::findOrFail($sessionId);
        $this->auditService->log('REMINDER_SENT', $session);
        return response()->json(['ok' => true, 'message' => 'Reminder sent']);
    }

    public function startSession(Request $request, int $sessionId)
    {
        $session = TutoringSession::findOrFail($sessionId);
        if ($session->tentor_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $session->update([
            'status' => 'ongoing',
            'check_in_time' => now(),
            'check_in_lat' => $request->input('latitude'),
            'check_in_lng' => $request->input('longitude'),
        ]);

        $this->auditService->log('SESSION_STARTED', $session);

        return back()->with('status', 'Sesi dimulai.');
    }

    public function markAttendance(Request $request, int $sessionId)
    {
        $session = TutoringSession::findOrFail($sessionId);

        $record = AttendanceRecord::create([
            'tutoring_session_id' => $session->id,
            'teacher_id' => $session->tentor_id,
            'student_id' => $session->student_id,
            'teacher_present' => true,
            'student_present' => (bool) $request->boolean('student_present', true),
            'teacher_lat' => $request->input('teacher_lat'),
            'teacher_lng' => $request->input('teacher_lng'),
            'student_lat' => $request->input('student_lat'),
            'student_lng' => $request->input('student_lng'),
            'location_status' => $request->input('location_status', 'DENIED'),
            'attendance_at' => now(),
        ]);

        $this->auditService->log('ATTENDANCE_MARKED', $record);

        return back()->with('status', 'Absensi direkam.');
    }

    public function submitMaterial(Request $request, int $sessionId)
    {
        $validated = $request->validate([
            'summary' => 'required|string',
            'homework' => 'nullable|string',
        ]);

        $report = MaterialReport::create([
            'tutoring_session_id' => $sessionId,
            'teacher_id' => auth()->id(),
            'summary' => $validated['summary'],
            'homework' => $validated['homework'] ?? null,
            'submitted_at' => now(),
        ]);

        $this->auditService->log('MATERIAL_SUBMITTED', $report);

        return back()->with('status', 'Ringkasan materi tersimpan.');
    }

    public function createDispute(Request $request)
    {
        $validated = $request->validate([
            'tutoring_session_id' => 'required|integer',
            'reason' => 'required|string|max:64',
            'description' => 'nullable|string',
        ]);

        $dispute = Dispute::create([
            'tutoring_session_id' => $validated['tutoring_session_id'],
            'created_by' => auth()->id(),
            'source_role' => auth()->user()?->getRoleNames()->first(),
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => 'DISPUTE_OPEN',
        ]);

        $this->auditService->log('DISPUTE_CREATED', $dispute);
        $this->discordAlertService->send('Dispute Created', [
            'dispute_id' => $dispute->id,
            'session_id' => $dispute->tutoring_session_id,
        ], 'warning');

        return back()->with('status', 'Dispute berhasil dibuat.');
    }

    public function updateDispute(Request $request, int $id)
    {
        $dispute = Dispute::findOrFail($id);
        $old = $dispute->toArray();

        $validated = $request->validate([
            'status' => 'required|in:IN_REVIEW_L1,IN_REVIEW_ADMIN,IN_REVIEW_SUPERADMIN,RESOLVED',
            'notes' => 'nullable|string',
        ]);

        $dispute->update(['status' => $validated['status']]);
        DisputeAction::create([
            'dispute_id' => $dispute->id,
            'actor_id' => auth()->id(),
            'action' => 'UPDATED',
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->auditService->log('DISPUTE_UPDATED', $dispute, $old, $dispute->toArray());

        return back()->with('status', 'Dispute diperbarui.');
    }

    public function resolveDispute(Request $request, int $id)
    {
        $dispute = Dispute::findOrFail($id);
        $old = $dispute->toArray();

        $dispute->update([
            'status' => 'RESOLVED',
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
        ]);

        DisputeAction::create([
            'dispute_id' => $dispute->id,
            'actor_id' => auth()->id(),
            'action' => 'RESOLVED',
            'notes' => $request->input('notes'),
        ]);

        $this->auditService->log('DISPUTE_RESOLVED', $dispute, $old, $dispute->toArray());

        return back()->with('status', 'Dispute selesai.');
    }

    public function createPayout(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|integer',
            'net_amount' => 'required|numeric|min:1',
        ]);

        $payout = TeacherPayout::create([
            'teacher_id' => $validated['teacher_id'],
            'gross_amount' => $validated['net_amount'],
            'deduction_amount' => 0,
            'net_amount' => $validated['net_amount'],
            'status' => 'PENDING',
        ]);

        $this->auditService->log('TEACHER_PAYOUT_CREATED', $payout);

        return back()->with('status', 'Payout dibuat.');
    }

    public function markPayoutPaid(int $id)
    {
        $payout = TeacherPayout::findOrFail($id);
        $payout->update([
            'status' => 'PAID',
            'paid_at' => now(),
            'reference_number' => 'PAYOUT-' . now()->format('YmdHis'),
        ]);

        $this->auditService->log('PAYOUT_PAID', $payout);

        return back()->with('status', 'Payout ditandai PAID.');
    }

    public function requestReschedule(Request $request)
    {
        $validated = $request->validate([
            'tutoring_session_id' => 'required|integer',
            'requested_start_at' => 'required|date',
            'requested_end_at' => 'required|date|after:requested_start_at',
            'reason' => 'nullable|string',
        ]);

        $req = RescheduleRequest::create([
            'tutoring_session_id' => $validated['tutoring_session_id'],
            'requested_by' => auth()->id(),
            'requested_start_at' => $validated['requested_start_at'],
            'requested_end_at' => $validated['requested_end_at'],
            'status' => 'PENDING',
            'reason' => $validated['reason'] ?? null,
        ]);

        $this->auditService->log('RESCHEDULE_REQUESTED', $req);

        return back()->with('status', 'Permintaan reschedule dikirim.');
    }

    public function approveReschedule(int $id)
    {
        $req = RescheduleRequest::findOrFail($id);
        $req->update([
            'status' => 'APPROVED',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);
        $this->auditService->log('RESCHEDULE_APPROVED', $req);
        return back()->with('status', 'Reschedule disetujui.');
    }

    public function denyReschedule(int $id)
    {
        $req = RescheduleRequest::findOrFail($id);
        $req->update([
            'status' => 'DENIED',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);
        $this->auditService->log('RESCHEDULE_DENIED', $req);
        return back()->with('status', 'Reschedule ditolak.');
    }
}
