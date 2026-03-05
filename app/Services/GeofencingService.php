<?php

namespace App\Services;

use App\Models\User;
use App\Models\FraudLog;
use Illuminate\Support\Facades\Log;

class GeofencingService
{
    protected $toleranceMeters = 100;

    /**
     * Check if Tentor is within range of Student
     */
    public function verifyLocation(User $tentor, User $student, float $currentLat, float $currentLng)
    {
        // 1. Calculate Distance between Tentor's current location and Student's registered location
        // Assuming student->latitude/longitude is the session location
        
        if (!$student->latitude || !$student->longitude) {
            // Fallback or skip if student location not set
            return true; 
        }

        $distance = $this->calculateDistance($currentLat, $currentLng, $student->latitude, $student->longitude); // in km
        $distanceMeters = $distance * 1000;

        if ($distanceMeters > $this->toleranceMeters) {
            // 2. Fraud Detection Logic
            $this->logPotentialFraud($tentor, $distanceMeters);
            
            return [
                'allowed' => false,
                'distance' => $distanceMeters,
                'message' => "Location verification failed. You are {$distanceMeters}m away from the student location (Max: {$this->toleranceMeters}m)."
            ];
        }

        return ['allowed' => true, 'distance' => $distanceMeters];
    }

    protected function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }

    protected function logPotentialFraud(User $tentor, float $distance)
    {
        $severity = $distance > 500 ? 100 : 50; // Higher severity if very far

        FraudLog::create([
            'user_id' => $tentor->id,
            'type' => 'geofence_mismatch',
            'description' => "Tentor attempted to start session {$distance}m away from location.",
            'severity_score' => $severity,
            'metadata' => [
                'distance' => $distance,
                'timestamp' => now()->toIso8601String()
            ]
        ]);

        if ($severity >= 100) {
            // Send Alert (Discord/Slack)
            Log::channel('fraud')->error("HIGH SEVERITY FRAUD ALERT: User #{$tentor->id} distance mismatch {$distance}m");
            // Here you would trigger the Discord Webhook notification job
        }
    }
}
