<?php

namespace App\Services;

use App\Models\TutoringSession;
use App\Models\TentorAvailability;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class BookingService
{
    /**
     * Book a session with High Concurrency Control
     * Uses Cache Locking + DB Transaction SERIALIZABLE/FOR UPDATE
     */
    public function bookSession(User $student, int $tentorId, int $subjectId, string $scheduledAt, int $durationMinutes)
    {
        $lockKey = "booking_lock_tentor_{$tentorId}_{$scheduledAt}";
        
        // 1. Atomic Lock (Redis/Cache) - Prevent multiple requests hitting DB at once
        $lock = Cache::lock($lockKey, 10); // 10 seconds lock

        if (!$lock->get()) {
            throw new \Exception("This slot is currently being processed by another user. Please try again in a moment.");
        }

        try {
            return DB::transaction(function () use ($student, $tentorId, $subjectId, $scheduledAt, $durationMinutes) {
                $startTime = Carbon::parse($scheduledAt);
                $endTime = $startTime->copy()->addMinutes($durationMinutes);

                // 2. Database Level Locking (Pessimistic Locking)
                // Select availability row and LOCK it
                // Note: We are locking the availability record, or we could lock the user record if availability is 1-to-many
                // Better approach: Check for overlapping sessions with FOR UPDATE
                
                // Check if Tentor exists and lock row
                $tentor = User::where('id', $tentorId)->lockForUpdate()->first();

                // 3. Collision Check (Strict)
                $hasConflict = TutoringSession::where('tentor_id', $tentorId)
                    ->whereIn('status', ['booked', 'ongoing', 'locked'])
                    ->where(function ($q) use ($startTime, $endTime) {
                        $q->whereBetween('scheduled_at', [$startTime, $endTime])
                          ->orWhereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) BETWEEN ? AND ?', [$startTime, $endTime]);
                    })
                    ->exists();

                if ($hasConflict) {
                    throw new \Exception("Double Booking Detected! This slot was just taken.");
                }

                // 4. Create Session (Locked Status initially)
                $session = TutoringSession::create([
                    'student_id' => $student->id,
                    'tentor_id' => $tentorId,
                    'subject_id' => $subjectId,
                    'scheduled_at' => $scheduledAt,
                    'duration_minutes' => $durationMinutes,
                    'status' => 'locked', // Temporary status before payment
                    'locked_at' => now(),
                    'locked_expires_at' => now()->addMinutes(3), // 3 Mins Lock Timeout
                ]);

                return $session;
            }); // End Transaction (Release DB Lock)

        } finally {
            $lock->release(); // Release Cache Lock
        }
    }
}
