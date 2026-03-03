<?php

namespace App\Services;

use App\Models\TentorProfile;
use App\Models\User;
use App\Models\TentorSkill;
use App\Models\TentorAvailability;
use Illuminate\Support\Facades\DB;

class TentorMatchingService
{
    /**
     * 4-Layer Filtering: Competency -> Geo-Radius -> Collision Check -> Ranking
     */
    public function findTentors(int $subjectId, float $lat, float $lng, string $scheduledAt, int $durationMinutes, int $radiusKm = 10)
    {
        // 1. Competency (Has the skill for the subject)
        $query = TentorProfile::query()
            ->with(['user', 'skills', 'availabilities'])
            ->whereHas('skills', function ($q) use ($subjectId) {
                $q->where('subject_id', $subjectId)
                  ->where('is_verified', true);
            });

        // 2. Geo-Radius (Haversine Formula)
        // Note: In production, use spatial types or a dedicated search engine (Elasticsearch/Meilisearch)
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support complex math functions by default in all environments.
            // For testing/development on SQLite, we'll calculate distance in PHP after retrieval
            // or just skip the SQL-level distance filter if we can't register functions.
            // We will fetch more records and filter in PHP.
        } else {
             $query->select('tentor_profiles.*')
                ->selectRaw(
                    '(6371 * acos(cos(radians(?)) * cos(radians(users.latitude)) * cos(radians(users.longitude) - radians(?)) + sin(radians(?)) * sin(radians(users.latitude)))) AS distance',
                    [$lat, $lng, $lat]
                )
                ->join('users', 'tentor_profiles.user_id', '=', 'users.id')
                ->having('distance', '<=', $radiusKm);
        }

        // 3. Collision Check (Availability & Existing Sessions)
        // Ensure tentor is available at the requested time (Day of week check)
        $requestedTime = \Carbon\Carbon::parse($scheduledAt);
        $dayOfWeek = $requestedTime->dayOfWeek; // 0 (Sunday) - 6 (Saturday)
        $startTime = $requestedTime->format('H:i:s');
        $endTime = $requestedTime->copy()->addMinutes($durationMinutes)->format('H:i:s');

        // Check availability slots
        $query->whereHas('availabilities', function ($q) use ($dayOfWeek, $startTime, $endTime) {
            $q->where('day_of_week', $dayOfWeek)
              ->where('start_time', '<=', $startTime)
              ->where('end_time', '>=', $endTime)
              ->where('is_available', true);
        });

        // Ensure no conflicting sessions (booked, ongoing, locked)
        $sessionStart = $requestedTime;
        $sessionEnd = $requestedTime->copy()->addMinutes($durationMinutes);

        // Conflict check using whereDoesntHave
        $query->whereDoesntHave('user.tentorSessions', function ($q) use ($sessionStart, $sessionEnd, $durationMinutes, $driver) {
             $q->where(function ($sub) use ($sessionStart, $sessionEnd, $durationMinutes, $driver) {
                // Check for overlap
                $sub->where(function ($s) use ($sessionStart, $sessionEnd, $durationMinutes, $driver) {
                    // Logic: (StartA < EndB) and (EndA > StartB)
                    // Existing session: scheduled_at (StartA), EndA = scheduled_at + duration
                    // Requested session: sessionStart (StartB), sessionEnd (EndB)
                    
                    $s->where('scheduled_at', '<', $sessionEnd);

                    if ($driver === 'sqlite') {
                        $s->whereRaw("datetime(scheduled_at, '+' || duration_minutes || ' minutes') > ?", [$sessionStart]);
                    } else {
                        $s->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$sessionStart]);
                    }
                });
            })->whereIn('status', ['booked', 'ongoing', 'locked']);
        });

        // Execute query
        $results = $query->get();

        // Post-processing for SQLite (Distance Calculation)
        if ($driver === 'sqlite') {
            $results->map(function ($profile) use ($lat, $lng) {
                $profile->distance = $this->calculateDistance(
                    $lat, 
                    $lng, 
                    $profile->user->latitude, 
                    $profile->user->longitude
                );
                return $profile;
            });
            
            // Filter by radius
            $results = $results->filter(function ($profile) use ($radiusKm) {
                return $profile->distance <= $radiusKm;
            });

            // Sort manually
            $results = $results->sortBy([
                ['rating', 'desc'],
                ['distance', 'asc'],
            ]);
            
            return $results->values();
        }

        // 4. Ranking (Rating & Distance) for MySQL
        $query->orderBy('rating', 'desc')
              ->orderBy('distance', 'asc');

        return $query->get();
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }
}
