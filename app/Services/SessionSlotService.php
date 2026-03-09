<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ScheduleSlot;
use App\Models\StudentPackageEntitlement;
use App\Models\StudentTutorMonthlyAssignment;
use App\Models\TentorProfile;
use App\Models\TutoringSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SessionSlotService
{
    public function canBookSlotForStudent(int $studentId, int $subjectId, int $slotId, int $bookingDay): bool
    {
        $slot = ScheduleSlot::query()->find($slotId);
        if (!$slot || strtoupper((string) $slot->status) !== 'OPEN') {
            return false;
        }

        [$start, $end] = $this->buildStartEndFromSlotAndDay($slot, $bookingDay);
        if (!$start || !$end) {
            return false;
        }
        if ($this->hasStudentConflict($studentId, $start, $end)) {
            return false;
        }

        return (bool) $this->pickTentorId($subjectId, $start, $end, null);
    }

    public function bookRecurringForStudent(int $studentId, int $subjectId, array $selections, int $invoiceId, string $deliveryMode = 'online', int $weeks = 4, ?StudentPackageEntitlement $entitlement = null): array
    {
        return DB::transaction(function () use ($studentId, $subjectId, $selections, $invoiceId, $deliveryMode, $weeks, $entitlement) {
            $invoice = Invoice::query()
                ->where('id', $invoiceId)
                ->where('user_id', $studentId)
                ->where('status', 'paid')
                ->first();
            if (!$invoice) {
                throw new \RuntimeException('Invoice tidak valid untuk booking.');
            }

            $sessionsToCreate = count($selections) * max(1, $weeks);
            if ($entitlement) {
                $entitlement = StudentPackageEntitlement::query()->lockForUpdate()->findOrFail($entitlement->id);
                if ($entitlement->invoice_id !== $invoice->id || $entitlement->user_id !== $studentId) {
                    throw new \RuntimeException('Hak paket tidak cocok dengan invoice booking.');
                }
                if ($entitlement->status !== 'ACTIVE' || (int) $entitlement->remaining_sessions < $sessionsToCreate) {
                    throw new \RuntimeException('Jatah sesi pada paket ini tidak mencukupi.');
                }
            }

            $created = [];
            foreach ($selections as $selection) {
                $slotId = (int) ($selection['slot_id'] ?? 0);
                $bookingDay = (int) ($selection['booking_day'] ?? -1);
                $slot = ScheduleSlot::query()->lockForUpdate()->findOrFail($slotId);
                if (strtoupper((string) $slot->status) !== 'OPEN') {
                    throw new \RuntimeException('Slot sesi tidak tersedia.');
                }

                [$baseStart, $baseEnd] = $this->buildStartEndFromSlotAndDay($slot, $bookingDay);
                if (!$baseStart || !$baseEnd) {
                    throw new \RuntimeException('Tanggal/jam sesi harus di masa depan.');
                }

                for ($w = 0; $w < max(1, $weeks); $w++) {
                    $start = $baseStart->copy()->addWeeks($w);
                    $end = $baseEnd->copy()->addWeeks($w);
                    $duration = (int) max(1, $start->diffInMinutes($end));
                    $monthKey = $start->format('Y-m');

                    $assignment = StudentTutorMonthlyAssignment::query()
                        ->where('student_id', $studentId)
                        ->where('subject_id', $subjectId)
                        ->where('month_key', $monthKey)
                        ->where('is_active', true)
                        ->first();

                    $primaryTentorId = $assignment?->tentor_id;
                    $selectedTentorId = $this->pickTentorId($subjectId, $start, $end, $primaryTentorId);
                    if (!$selectedTentorId) {
                        throw new \RuntimeException('Belum ada tentor tersedia untuk salah satu jadwal yang dipilih.');
                    }

                    if (!$assignment) {
                        StudentTutorMonthlyAssignment::query()->create([
                            'student_id' => $studentId,
                            'subject_id' => $subjectId,
                            'tentor_id' => $selectedTentorId,
                            'month_key' => $monthKey,
                            'is_active' => true,
                        ]);
                        $primaryTentorId = $selectedTentorId;
                    }

                    if ($this->hasStudentConflict($studentId, $start, $end)) {
                        throw new \RuntimeException('Ada bentrok jadwal pada tanggal/jam yang dipilih.');
                    }

                    $created[] = TutoringSession::query()->create([
                        'student_id' => $studentId,
                        'tentor_id' => $selectedTentorId,
                        'primary_tentor_id' => $primaryTentorId,
                        'is_substitute' => $primaryTentorId !== $selectedTentorId,
                        'subject_id' => $subjectId,
                        'invoice_id' => $invoice->id,
                        'schedule_slot_id' => $slot->id,
                        'scheduled_at' => $start,
                        'duration_minutes' => $duration,
                        'delivery_mode' => strtolower($deliveryMode) === 'offline' ? 'offline' : 'online',
                        'status' => 'booked',
                    ]);
                }
            }

            if ($entitlement) {
                $usedSessions = (int) $entitlement->used_sessions + count($created);
                $remainingSessions = max(0, (int) $entitlement->total_sessions - $usedSessions);
                $entitlement->update([
                    'used_sessions' => $usedSessions,
                    'remaining_sessions' => $remainingSessions,
                    'status' => $remainingSessions > 0 ? 'ACTIVE' : 'EXHAUSTED',
                ]);
            }

            return $created;
        });
    }

    private function buildStartEndFromSlotAndDay(ScheduleSlot $slot, int $bookingDay): array
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

        // If selected day/time has passed for current week, roll to next week.
        if ($start->lessThanOrEqualTo(now())) {
            $start->addWeek();
            $end->addWeek();
        }

        return [$start, $end];
    }

    public function reassignForReschedule(TutoringSession $session, Carbon $newStart, Carbon $newEnd): TutoringSession
    {
        $duration = (int) max(1, $newStart->diffInMinutes($newEnd));
        $monthKey = $newStart->format('Y-m');

        $assignment = StudentTutorMonthlyAssignment::query()
            ->where('student_id', $session->student_id)
            ->where('subject_id', $session->subject_id)
            ->where('month_key', $monthKey)
            ->where('is_active', true)
            ->first();

        $preferredTentor = $assignment?->tentor_id ?: $session->primary_tentor_id;
        $tentorId = $this->pickTentorId($session->subject_id, $newStart, $newEnd, $preferredTentor);
        if (!$tentorId) {
            throw new \RuntimeException('Tidak ada tentor tersedia di jadwal pengganti.');
        }

        if (!$assignment) {
            StudentTutorMonthlyAssignment::query()->create([
                'student_id' => $session->student_id,
                'subject_id' => $session->subject_id,
                'tentor_id' => $tentorId,
                'month_key' => $monthKey,
                'is_active' => true,
            ]);
            $preferredTentor = $tentorId;
        }

        $session->update([
            'tentor_id' => $tentorId,
            'primary_tentor_id' => $preferredTentor,
            'is_substitute' => $preferredTentor !== $tentorId,
            'scheduled_at' => $newStart,
            'duration_minutes' => $duration,
            'status' => 'booked',
        ]);

        return $session->fresh();
    }

    private function pickTentorId(int $subjectId, Carbon $start, Carbon $end, ?int $preferredTentorId = null): ?int
    {
        $candidateIds = TentorProfile::query()
            ->join('tentor_skills', 'tentor_skills.tentor_profile_id', '=', 'tentor_profiles.id')
            ->where('tentor_skills.subject_id', $subjectId)
            ->where('tentor_skills.is_verified', true)
            ->where('tentor_profiles.is_verified', true)
            ->pluck('tentor_profiles.user_id')
            ->unique()
            ->values()
            ->all();

        if (empty($candidateIds)) {
            return null;
        }

        $ordered = $candidateIds;
        if ($preferredTentorId && in_array($preferredTentorId, $candidateIds, true)) {
            $ordered = array_values(array_unique(array_merge([$preferredTentorId], $candidateIds)));
        }

        foreach ($ordered as $tentorId) {
            if (!$this->hasConflict((int) $tentorId, $start, $end)) {
                return (int) $tentorId;
            }
        }

        return null;
    }

    private function hasConflict(int $tentorId, Carbon $start, Carbon $end): bool
    {
        $driver = DB::connection()->getDriverName();

        return TutoringSession::query()
            ->where('tentor_id', $tentorId)
            ->whereIn('status', ['booked', 'ongoing', 'locked'])
            ->where('scheduled_at', '<', $end)
            ->where(function ($query) use ($driver, $start) {
                if ($driver === 'sqlite') {
                    $query->whereRaw("datetime(scheduled_at, '+' || duration_minutes || ' minutes') > ?", [$start]);
                } else {
                    $query->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$start]);
                }
            })
            ->exists();
    }

    private function hasStudentConflict(int $studentId, Carbon $start, Carbon $end): bool
    {
        $driver = DB::connection()->getDriverName();

        return TutoringSession::query()
            ->where('student_id', $studentId)
            ->whereIn('status', ['booked', 'ongoing', 'locked', 'confirmed'])
            ->where('scheduled_at', '<', $end)
            ->where(function ($query) use ($driver, $start) {
                if ($driver === 'sqlite') {
                    $query->whereRaw("datetime(scheduled_at, '+' || duration_minutes || ' minutes') > ?", [$start]);
                } else {
                    $query->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$start]);
                }
            })
            ->exists();
    }
}
