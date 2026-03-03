<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\TentorAvailability;
use App\Models\TentorProfile;
use App\Models\TentorSkill;
use App\Models\User;
use App\Services\TentorMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_matching_logic()
    {
        // 1. Setup Data
        // Subject
        $subject = Subject::create(['name' => 'Math', 'level' => 'SMA']);

        // Tentor 1: Nearby, Skilled, Available (Perfect Match)
        $tentor1 = User::factory()->create([
            'latitude' => -6.200000,
            'longitude' => 106.816666, // Jakarta Center
        ]);
        $profile1 = TentorProfile::create(['user_id' => $tentor1->id, 'rating' => 4.8]);
        TentorSkill::create([
            'tentor_profile_id' => $profile1->id,
            'subject_id' => $subject->id,
            'hourly_rate' => 100000,
            'is_verified' => true
        ]);
        TentorAvailability::create([
            'tentor_profile_id' => $profile1->id,
            'day_of_week' => 1, // Monday
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'is_available' => true
        ]);

        // Tentor 2: Far away (> 10km)
        $tentor2 = User::factory()->create([
            'latitude' => -6.500000, // Bogor (approx 30-40km away)
            'longitude' => 106.816666,
        ]);
        $profile2 = TentorProfile::create(['user_id' => $tentor2->id, 'rating' => 5.0]);
        TentorSkill::create([
            'tentor_profile_id' => $profile2->id,
            'subject_id' => $subject->id,
            'hourly_rate' => 100000,
            'is_verified' => true
        ]);
        TentorAvailability::create([
            'tentor_profile_id' => $profile2->id,
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'is_available' => true
        ]);

        // Tentor 3: Nearby, but NOT available at time
        $tentor3 = User::factory()->create([
            'latitude' => -6.201000,
            'longitude' => 106.817000,
        ]);
        $profile3 = TentorProfile::create(['user_id' => $tentor3->id, 'rating' => 4.5]);
        TentorSkill::create([
            'tentor_profile_id' => $profile3->id,
            'subject_id' => $subject->id,
            'hourly_rate' => 100000,
            'is_verified' => true
        ]);
        // Available on Monday but later
        TentorAvailability::create([
            'tentor_profile_id' => $profile3->id,
            'day_of_week' => 1,
            'start_time' => '13:00:00', // Late
            'end_time' => '15:00:00',
            'is_available' => true
        ]);

        // 2. Execute Matching
        $service = new TentorMatchingService();
        // Search for Monday 09:00, 10km radius
        // Use a fixed date that is a Monday. e.g. 2026-03-02 is a Monday
        $scheduledAt = '2026-03-02 09:00:00'; 
        $results = $service->findTentors(
            $subject->id,
            -6.200000, 
            106.816666, 
            $scheduledAt,
            60, // 60 mins
            10 // 10 km
        );

        // 3. Assertions
        $this->assertCount(1, $results);
        $this->assertEquals($profile1->id, $results->first()->id);
        
        // Verify ranking logic (if we add another valid tentor with lower rating)
        // Tentor 4: Nearby, Available, Lower Rating
        $tentor4 = User::factory()->create([
            'latitude' => -6.202000,
            'longitude' => 106.818000,
        ]);
        $profile4 = TentorProfile::create(['user_id' => $tentor4->id, 'rating' => 3.5]);
        TentorSkill::create([
            'tentor_profile_id' => $profile4->id,
            'subject_id' => $subject->id,
            'hourly_rate' => 80000,
            'is_verified' => true
        ]);
        TentorAvailability::create([
            'tentor_profile_id' => $profile4->id,
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'is_available' => true
        ]);

        $results2 = $service->findTentors(
            $subject->id,
            -6.200000, 
            106.816666, 
            $scheduledAt,
            60, 
            10
        );

        $this->assertCount(2, $results2);
        // Should be ordered by rating desc
        $this->assertEquals($profile1->id, $results2[0]->id); // Rating 4.8
        $this->assertEquals($profile4->id, $results2[1]->id); // Rating 3.5
    }
}
