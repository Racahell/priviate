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
use App\Models\StudentPackageEntitlement;
use App\Models\TeacherPayout;
use App\Models\TutoringSession;
use App\Models\User;
use App\Services\AuditService;
use App\Services\DiscordAlertService;
use App\Services\EscrowService;
use App\Services\SessionSlotService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperationsController extends Controller
{
    private const RESCHEDULE_LIMIT_PER_SESSION = 2;
    private const RESCHEDULE_MIN_HOURS_BEFORE_SESSION = 6;

    public function __construct(
        private readonly AuditService $auditService,
        private readonly DiscordAlertService $discordAlertService,
        private readonly EscrowService $escrowService,
        private readonly SessionSlotService $sessionSlotService
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
            'notes' => "Draft invoice package #{$package->id} {$package->name}",
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => "Paket #{$package->id}: {$package->name} | Snapshot harga saat checkout",
            'quantity' => 1,
            'unit_price' => $price->price,
            'amount' => $price->price,
        ]);

        $this->auditService->log('PACKAGE_SELECTED', $invoice, [], [
            'package_id' => $package->id,
            'price' => $price->price,
        ]);

        return redirect()->route('student.invoices')->with('status', "Paket dipilih. Draft invoice #{$invoice->invoice_number} dibuat.");
    }

    public function paymentSuccess(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|integer',
            'amount' => 'required|numeric|min:1',
            'transaction_id' => 'nullable|string|max:255',
            'method' => 'required|in:bank_transfer,virtual_account,ewallet,qris',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);
        abort_unless((int) $invoice->user_id === (int) auth()->id(), 403);
        if (strtolower((string) $invoice->status) === 'paid') {
            return back()->withErrors(['invoice_id' => 'Invoice sudah dibayar.']);
        }
        if (strtolower((string) $invoice->status) === 'cancelled') {
            return back()->withErrors(['invoice_id' => 'Invoice sudah dibatalkan dan tidak bisa dibayar.']);
        }
        if ((float) $validated['amount'] !== (float) $invoice->total_amount) {
            return back()->withErrors(['amount' => 'Nominal pembayaran harus sesuai total invoice.']);
        }

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method' => $validated['method'],
            'transaction_id' => $validated['transaction_id'] ?: ('PAY-' . $invoice->id . '-' . now()->format('YmdHis')),
            'amount' => $validated['amount'],
            'status' => 'success',
            'paid_at' => now(),
        ]);

        $invoice->update(['status' => 'paid']);
        try {
            $this->ensurePackageEntitlement($invoice);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['invoice_id' => $e->getMessage()]);
        }

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

    public function bookSlot(Request $request)
    {
        abort_unless($request->user()?->hasRole('siswa'), 403);

        $validated = $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'booking_days' => 'required|array|min:1',
            'booking_days.*' => 'required|integer|between:0,6',
            'slot_ids' => 'required|array|min:1',
            'slot_ids.*' => 'required|integer|exists:schedule_slots,id',
            'delivery_mode' => 'required|in:online,offline',
        ]);

        $invoice = Invoice::query()
            ->where('id', (int) $validated['invoice_id'])
            ->where('user_id', (int) auth()->id())
            ->first();
        if (!$invoice) {
            return back()->withErrors(['invoice_id' => 'Invoice tidak ditemukan.'])->withInput();
        }
        if (strtolower((string) $invoice->status) !== 'paid') {
            return back()->withErrors(['invoice_id' => 'Invoice belum dibayar.'])->withInput();
        }
        try {
            $entitlement = $this->ensurePackageEntitlement($invoice);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['invoice_id' => $e->getMessage()])->withInput();
        }
        if ($entitlement->status !== 'ACTIVE' || (int) $entitlement->remaining_sessions <= 0) {
            return back()->withErrors(['invoice_id' => 'Jatah sesi dari invoice ini sudah habis.'])->withInput();
        }

        $bookingPlan = $this->resolveBookingPlanForInvoice($invoice);
        $weeklyQuota = (int) $bookingPlan['quota'];
        $bookingWeeks = (int) $bookingPlan['weeks'];
        $isTrial = (bool) $bookingPlan['is_trial'];
        $requiredSessions = max(1, $weeklyQuota * $bookingWeeks);
        if ((int) $entitlement->remaining_sessions < $requiredSessions) {
            return back()->withErrors([
                'invoice_id' => 'Sisa sesi pada paket ini tidak cukup untuk booking yang dipilih.',
            ])->withInput();
        }
        $days = collect($validated['booking_days'] ?? [])->values();
        $slotIds = collect($validated['slot_ids'] ?? [])->values();
        $count = min($days->count(), $slotIds->count());

        $selections = collect(range(0, max(0, $count - 1)))
            ->map(function ($idx) use ($days, $slotIds) {
                return [
                    'booking_day' => (int) $days[$idx],
                    'slot_id' => (int) $slotIds[$idx],
                ];
            })
            ->filter(fn ($row) => isset($row['booking_day']) && !empty($row['slot_id']))
            ->values();

        if ($selections->count() !== $weeklyQuota) {
            return back()->withErrors([
                'slot_ids' => "Jumlah pilihan sesi per minggu harus {$weeklyQuota} sesuai paket aktif Anda.",
            ])->withInput();
        }

        try {
            $sessions = $this->sessionSlotService->bookRecurringForStudent(
                (int) auth()->id(),
                (int) $validated['subject_id'],
                $selections->all(),
                (int) $invoice->id,
                (string) $validated['delivery_mode'],
                $bookingWeeks,
                $entitlement
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['slot_ids' => $e->getMessage()])->withInput();
        }

        $this->auditService->log('SLOT_BOOKED_BY_STUDENT', null, [], [
            'slot_ids' => $selections->pluck('slot_id')->all(),
            'booking_days' => $selections->pluck('booking_day')->all(),
            'subject_id' => $validated['subject_id'],
            'sessions_created' => count($sessions),
            'delivery_mode' => $validated['delivery_mode'],
        ]);

        return back()->with('status', $isTrial
            ? 'Sesi trial berhasil dibooking (1 pertemuan).'
            : 'Sesi berhasil dibooking untuk 1 bulan sesuai paket Anda.'
        );
    }

    public function slotAvailability(Request $request)
    {
        abort_unless($request->user()?->hasRole('siswa'), 403);

        $validated = $request->validate([
            'subject_id' => 'required|integer|exists:subjects,id',
            'booking_day' => 'required|integer|between:0,6',
        ]);

        $studentId = (int) $request->user()->id;
        $subjectId = (int) $validated['subject_id'];
        $bookingDay = (int) $validated['booking_day'];

        $slots = ScheduleSlot::query()
            ->where('status', 'OPEN')
            ->orderBy('start_at')
            ->get(['id']);

        $availableSlotIds = $slots
            ->filter(fn ($slot) => $this->sessionSlotService->canBookSlotForStudent($studentId, $subjectId, (int) $slot->id, $bookingDay))
            ->pluck('id')
            ->values();

        return response()->json([
            'available_slot_ids' => $availableSlotIds,
        ]);
    }

    private function resolveBookingPlanForInvoice(Invoice $invoice): array
    {
        $invoice->loadMissing('items');

        $packageId = null;
        $package = null;
        $notes = (string) ($invoice->notes ?? '');
        if (preg_match('/package\s*#\s*(\d+)/i', $notes, $m)) {
            $packageId = (int) $m[1];
        }

        if (!$packageId) {
            foreach ($invoice->items as $item) {
                $desc = (string) ($item->description ?? '');
                if (preg_match('/Paket\s*#\s*(\d+)\s*:/i', $desc, $m)) {
                    $packageId = (int) $m[1];
                    break;
                }
                if (preg_match('/Paket:\s*([^|]+)/i', $desc, $m)) {
                    $pkgName = trim((string) $m[1]);
                    $packageId = (int) (Package::query()->where('name', $pkgName)->value('id') ?? 0);
                    if ($packageId > 0) {
                        break;
                    }
                }
            }
        }

        if (!$packageId) {
            $price = (float) $invoice->total_amount;
            $packageId = (int) (PackagePrice::query()
                ->where('is_active', true)
                ->where('price', $price)
                ->orderByDesc('id')
                ->value('package_id') ?? 0);
        }

        if ($packageId) {
            $package = Package::query()->find($packageId);
        }

        $quota = (int) (PackageQuota::query()
            ->where('package_id', $packageId)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->value('quota') ?? 1);

        $isTrial = (bool) ($package?->trial_enabled ?? false);

        return [
            'id' => (int) $packageId,
            'quota' => $isTrial ? 1 : max(1, $quota),
            'weeks' => $isTrial ? 1 : 4,
            'is_trial' => $isTrial,
        ];
    }

    private function ensurePackageEntitlement(Invoice $invoice): StudentPackageEntitlement
    {
        if (!Schema::hasTable('student_package_entitlements')) {
            throw new \RuntimeException('Tabel entitlement paket belum tersedia. Jalankan migrasi terbaru terlebih dahulu.');
        }

        $invoice->loadMissing('packageEntitlement');

        if ($invoice->packageEntitlement) {
            return $invoice->packageEntitlement;
        }

        $plan = $this->resolveBookingPlanForInvoice($invoice);
        $totalSessions = max(1, (int) $plan['quota'] * (int) $plan['weeks']);

        return StudentPackageEntitlement::query()->create([
            'user_id' => (int) $invoice->user_id,
            'package_id' => (int) ($plan['id'] ?: 0) ?: null,
            'invoice_id' => (int) $invoice->id,
            'weekly_quota' => (int) $plan['quota'],
            'booking_weeks' => (int) $plan['weeks'],
            'total_sessions' => $totalSessions,
            'used_sessions' => 0,
            'remaining_sessions' => $totalSessions,
            'is_trial' => (bool) $plan['is_trial'],
            'status' => 'ACTIVE',
        ]);
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

    public function sendReminder(Request $request, int $sessionId)
    {
        $session = TutoringSession::findOrFail($sessionId);
        $this->auditService->log('REMINDER_SENT', $session);
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Reminder sent']);
        }

        return back()->with('status', 'Reminder berhasil dikirim.');
    }

    public function startSession(Request $request, int $sessionId)
    {
        $session = TutoringSession::findOrFail($sessionId);
        if ($session->tentor_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }
        if ($session->scheduled_at && now()->lt($session->scheduled_at)) {
            return back()->withErrors(['status' => 'Sesi belum bisa dimulai sebelum jam yang dijadwalkan.']);
        }

        $location = $this->normalizeLocationPayload($request, 'latitude', 'longitude');

        $session->update([
            'status' => 'ongoing',
            'check_in_time' => now(),
            'check_in_lat' => $location['latitude'],
            'check_in_lng' => $location['longitude'],
        ]);

        $this->auditService->log('SESSION_STARTED', $session);

        return back()->with('status', 'Sesi dimulai.');
    }

    public function markAttendance(Request $request, int $sessionId)
    {
        $session = TutoringSession::findOrFail($sessionId);
        if ($session->tentor_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }
        if ($session->scheduled_at && now()->lt($session->scheduled_at)) {
            return back()->withErrors(['status' => 'Absensi belum bisa dilakukan sebelum jam sesi dimulai.']);
        }

        $teacherLocation = $this->normalizeLocationPayload($request, 'teacher_lat', 'teacher_lng');

        $record = AttendanceRecord::create([
            'tutoring_session_id' => $session->id,
            'teacher_id' => $session->tentor_id,
            'student_id' => $session->student_id,
            'teacher_present' => true,
            'student_present' => (bool) $request->boolean('student_present', true),
            'teacher_lat' => $teacherLocation['latitude'],
            'teacher_lng' => $teacherLocation['longitude'],
            'student_lat' => $request->input('student_lat'),
            'student_lng' => $request->input('student_lng'),
            'location_status' => $teacherLocation['location_status'],
            'attendance_at' => now(),
        ]);

        $this->auditService->log('ATTENDANCE_MARKED', $record);

        return back()->with('status', 'Absensi direkam.');
    }

    private function normalizeLocationPayload(Request $request, string $latitudeKey, string $longitudeKey): array
    {
        $status = strtoupper((string) $request->input('location_status', 'DENIED'));
        if ($status !== 'ALLOW') {
            return [
                'location_status' => 'DENIED',
                'latitude' => null,
                'longitude' => null,
            ];
        }

        $latitude = $request->input($latitudeKey);
        $longitude = $request->input($longitudeKey);

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

    public function submitMaterial(Request $request, int $sessionId)
    {
        $validated = $request->validate([
            'summary' => 'required|string',
            'homework' => 'nullable|string',
        ]);

        $session = TutoringSession::findOrFail($sessionId);
        if ($session->tentor_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }
        if ($session->scheduled_at && now()->lt($session->scheduled_at)) {
            return back()->withErrors(['summary' => 'Ringkasan materi belum bisa diisi sebelum jam sesi dimulai.']);
        }

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

        $session = $this->resolveAccessibleSession($request, (int) $validated['tutoring_session_id']);

        $dispute = Dispute::create([
            'tutoring_session_id' => $session->id,
            'created_by' => auth()->id(),
            'source_role' => auth()->user()?->getRoleNames()->first(),
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => 'DISPUTE_OPEN',
        ]);

        $this->auditService->log('DISPUTE_CREATED', $dispute);
        $this->discordAlertService->send('Kritik Created', [
            'dispute_id' => $dispute->id,
            'session_id' => $dispute->tutoring_session_id,
        ], 'warning');

        return back()->with('status', 'Kritik berhasil dibuat.');
    }

    public function updateDispute(Request $request, int $id)
    {
        $dispute = Dispute::findOrFail($id);
        $old = $dispute->toArray();

        $validated = $request->validate([
            'status' => 'required|in:IN_REVIEW_L1,IN_REVIEW_ADMIN,RESOLVED',
            'notes' => 'nullable|string',
        ]);

        $dispute->update(['status' => $validated['status']]);
        DisputeAction::create([
            'dispute_id' => $dispute->id,
            'actor_id' => auth()->id(),
            'action' => 'UPDATED',
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->sendDisputeReplyEmail($dispute, (string) ($validated['notes'] ?? ''), (string) $validated['status']);

        $this->auditService->log('DISPUTE_UPDATED', $dispute, $old, $dispute->toArray());

        return back()->with('status', 'Kritik diperbarui.');
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

        $this->sendDisputeReplyEmail($dispute, (string) $request->input('notes', ''), 'RESOLVED');

        $this->auditService->log('DISPUTE_RESOLVED', $dispute, $old, $dispute->toArray());

        return back()->with('status', 'Kritik selesai.');
    }

    public function createPayout(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|integer|exists:tutoring_sessions,id',
            'net_amount' => 'required|numeric|min:1',
        ]);

        $session = TutoringSession::query()
            ->with('materialReport')
            ->findOrFail((int) $validated['session_id']);

        if ($session->tentor_id === null) {
            return back()->withErrors(['session_id' => 'Sesi ini belum memiliki tentor.'])->withInput();
        }

        if (!$this->isSessionEligibleForPayout($session)) {
            return back()->withErrors([
                'session_id' => 'Payout hanya bisa dibuat untuk sesi yang sudah selesai dan sudah memiliki laporan materi.',
            ])->withInput();
        }

        $existingPayout = TeacherPayout::query()
            ->where('tutoring_session_id', $session->id)
            ->exists();

        if ($existingPayout) {
            return back()->withErrors(['session_id' => 'Sesi ini sudah memiliki payout.'])->withInput();
        }

        $payout = TeacherPayout::create([
            'teacher_id' => $session->tentor_id,
            'tutoring_session_id' => $session->id,
            'gross_amount' => $validated['net_amount'],
            'deduction_amount' => 0,
            'net_amount' => $validated['net_amount'],
            'status' => 'PENDING',
        ]);

        $this->auditService->log('TEACHER_PAYOUT_CREATED', $payout);

        return back()->with('status', 'Payout dibuat.');
    }

    private function isSessionEligibleForPayout(TutoringSession $session): bool
    {
        $status = strtolower((string) $session->status);

        return in_array($status, ['completed', 'auto_completed'], true)
            && $session->materialReport()->exists();
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
            'booking_day' => 'required|integer|between:0,6',
            'schedule_slot_id' => 'required|integer|exists:schedule_slots,id',
            'reason' => 'nullable|string',
        ]);

        $session = $this->resolveAccessibleSession($request, (int) $validated['tutoring_session_id']);
        $slot = ScheduleSlot::query()->findOrFail((int) $validated['schedule_slot_id']);
        if (strtoupper((string) $slot->status) !== 'OPEN') {
            return back()->withErrors(['schedule_slot_id' => 'Jam sesi tidak tersedia.'])->withInput();
        }
        if (!$this->canRequestReschedule($session)) {
            return back()->withErrors([
                'tutoring_session_id' => 'Reschedule hanya bisa diajukan maksimal 2 kali dan minimal 6 jam sebelum sesi dimulai.',
            ])->withInput();
        }
        $existingPendingRequest = RescheduleRequest::query()
            ->where('tutoring_session_id', $session->id)
            ->whereIn('status', ['PENDING', 'pending'])
            ->exists();
        if ($existingPendingRequest) {
            return back()->withErrors([
                'tutoring_session_id' => 'Masih ada permintaan reschedule yang menunggu persetujuan.',
            ])->withInput();
        }

        [$newStart, $newEnd] = $this->buildRescheduleDateTimeFromDayAndSlot($slot, (int) $validated['booking_day']);
        if (!$newStart || !$newEnd) {
            return back()->withErrors(['booking_day' => 'Hari/jam reschedule tidak valid.'])->withInput();
        }
        if ($session->scheduled_at && $newStart->equalTo($session->scheduled_at)) {
            return back()->withErrors(['booking_day' => 'Jadwal baru tidak boleh sama dengan jadwal saat ini.'])->withInput();
        }

        $req = RescheduleRequest::create([
            'tutoring_session_id' => $session->id,
            'requested_by' => auth()->id(),
            'requested_start_at' => $newStart,
            'requested_end_at' => $newEnd,
            'status' => 'PENDING',
            'reason' => $validated['reason'] ?? null,
        ]);

        $this->auditService->log('RESCHEDULE_REQUESTED', $req);

        return back()->with('status', 'Permintaan reschedule dikirim.');
    }

    private function buildRescheduleDateTimeFromDayAndSlot(ScheduleSlot $slot, int $bookingDay): array
    {
        if ($bookingDay < 0 || $bookingDay > 6) {
            return [null, null];
        }

        $today = now()->startOfDay();
        $slotStart = Carbon::parse($slot->start_at);
        $slotEnd = Carbon::parse($slot->end_at);

        $dayDiff = ($bookingDay - $today->dayOfWeek + 7) % 7;
        $targetDate = $today->copy()->addDays($dayDiff);
        $start = $targetDate->copy()->setTime($slotStart->hour, $slotStart->minute, $slotStart->second);
        $end = $targetDate->copy()->setTime($slotEnd->hour, $slotEnd->minute, $slotEnd->second);

        if ($slotEnd->lessThanOrEqualTo($slotStart)) {
            $end->addDay();
        }

        if ($start->lessThanOrEqualTo(now())) {
            $start->addWeek();
            $end->addWeek();
        }

        return [$start, $end];
    }

    public function approveReschedule(int $id)
    {
        $req = RescheduleRequest::findOrFail($id);
        if (strtoupper((string) $req->status) !== 'PENDING') {
            return back()->withErrors(['status' => 'Reschedule ini sudah diproses sebelumnya.']);
        }
        $session = TutoringSession::findOrFail($req->tutoring_session_id);

        try {
            $this->sessionSlotService->reassignForReschedule(
                $session,
                \Carbon\Carbon::parse($req->requested_start_at),
                \Carbon\Carbon::parse($req->requested_end_at)
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

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
        if (strtoupper((string) $req->status) !== 'PENDING') {
            return back()->withErrors(['status' => 'Reschedule ini sudah diproses sebelumnya.']);
        }
        $req->update([
            'status' => 'DENIED',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);
        $this->auditService->log('RESCHEDULE_DENIED', $req);
        return back()->with('status', 'Reschedule ditolak.');
    }

    private function canRequestReschedule(TutoringSession $session): bool
    {
        $status = strtolower((string) $session->status);
        if (!in_array($status, ['booked', 'confirmed', 'ongoing'], true)) {
            return false;
        }

        if (!$session->scheduled_at || now()->diffInHours($session->scheduled_at, false) < self::RESCHEDULE_MIN_HOURS_BEFORE_SESSION) {
            return false;
        }

        $requestCount = RescheduleRequest::query()
            ->where('tutoring_session_id', $session->id)
            ->count();

        return $requestCount < self::RESCHEDULE_LIMIT_PER_SESSION;
    }

    private function resolveAccessibleSession(Request $request, int $sessionId): TutoringSession
    {
        $user = $request->user();
        $session = TutoringSession::query()
            ->with('student:id,parent_id')
            ->findOrFail($sessionId);

        if ($user?->hasAnyRole(['superadmin', 'owner', 'admin'])) {
            return $session;
        }

        if ($user?->hasRole('orang_tua') && (int) $session->student?->parent_id === (int) $user->id) {
            return $session;
        }

        if ($user?->hasRole('siswa') && (int) $session->student_id === (int) $user->id) {
            return $session;
        }

        if ($user?->hasRole('tentor') && (int) $session->tentor_id === (int) $user->id) {
            return $session;
        }

        abort(403, 'Unauthorized');
    }

    private function sendDisputeReplyEmail(Dispute $dispute, string $notes, string $status): void
    {
        if (trim($notes) === '') {
            return;
        }

        $creator = User::query()->find($dispute->created_by);
        if (!$creator || empty($creator->email)) {
            return;
        }

        try {
            Mail::to($creator->email)->send(new \App\Mail\DisputeReplyMail(
                $dispute,
                $notes,
                $status,
                auth()->user()?->name,
                $creator->name
            ));
        } catch (\Throwable $e) {
            Log::warning('Failed to send dispute reply email.', [
                'dispute_id' => $dispute->id,
                'recipient' => $creator->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
