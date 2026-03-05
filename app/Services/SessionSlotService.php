<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ScheduleSlot;
use App\Models\StudentTutorMonthlyAssignment;
use App\Models\TentorAvailability;
use App\Models\TentorProfile;
use App\Models\TutoringSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SessionSlotService
{
    public function bookForStudent(int $studentId, int $subjectId, int $slotId): TutoringSession
    {
        return DB::transaction(function () use ($studentId, $subjectId, $slotId) {
            $paidInvoiceId = Invoice::query()
                ->where('user_id', $studentId)
                ->where('status', 'paid')
                ->latest('id')
                ->value('id');

            if (!$paidInvoiceId) {
                throw new \RuntimeException('Booking sesi hanya bisa setelah pembayaran berhasil.');
            }

            $slot = ScheduleSlot::query()->lockForUpdate()->findOrFail($slotId);
            if (strtoupper((string) $slot->status) !== 'OPEN') {
                throw new \RuntimeException('Slot sesi sudah diambil. Pilih slot lain.');
            }

            $start = Carbon::parse($slot->start_at);
            $end = Carbon::parse($slot->end_at);
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
                throw new \RuntimeException('Belum ada tentor tersedia untuk mapel & slot ini.');
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

            $slot->update([
                'subject_id' => $subjectId,
                'student_id' => $studentId,
                'tentor_id' => $selectedTentorId,
                'status' => 'BOOKED',
                'locked_at' => now(),
            ]);

            return TutoringSession::query()->create([
                'student_id' => $studentId,
                'tentor_id' => $selectedTentorId,
                'primary_tentor_id' => $primaryTentorId,
                'is_substitute' => $primaryTentorId !== $selectedTentorId,
                'subject_id' => $subjectId,
                'invoice_id' => $paidInvoiceId,
                'schedule_slot_id' => $slot->id,
                'scheduled_at' => $start,
                'duration_minutes' => $duration,
                'status' => 'booked',
            ]);
        });
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

        $day = $start->dayOfWeek;
        $startTime = $start->format('H:i:s');
        $endTime = $end->format('H:i:s');

        $availableTentorIds = TentorAvailability::query()
            ->join('tentor_profiles', 'tentor_profiles.id', '=', 'tentor_availabilities.tentor_profile_id')
            ->whereIn('tentor_profiles.user_id', $candidateIds)
            ->where('tentor_availabilities.day_of_week', $day)
            ->where('tentor_availabilities.is_available', true)
            ->where('tentor_availabilities.start_time', '<=', $startTime)
            ->where('tentor_availabilities.end_time', '>=', $endTime)
            ->pluck('tentor_profiles.user_id')
            ->unique()
            ->values()
            ->all();

        if (empty($availableTentorIds)) {
            return null;
        }

        $ordered = $availableTentorIds;
        if ($preferredTentorId && in_array($preferredTentorId, $availableTentorIds, true)) {
            $ordered = array_values(array_unique(array_merge([$preferredTentorId], $availableTentorIds)));
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
}

