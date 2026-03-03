<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Subject;
use App\Models\TutoringSession;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\InvoiceRepository;
use App\Services\GeofencingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EnterpriseTestPart2 extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_sequence_generation()
    {
        $repo = new InvoiceRepository();
        $user = User::factory()->create();

        // 1. Generate First Invoice
        $inv1 = $repo->create([
            'user_id' => $user->id,
            'total_amount' => 100000,
            'status' => 'unpaid',
            'issue_date' => now(),
            'due_date' => now()->addDays(7),
        ]);

        $year = now()->format('Y');
        $month = now()->format('m');
        $expected1 = "SIM-LP/{$year}/{$month}/00001";
        $this->assertEquals($expected1, $inv1->invoice_number);

        // 2. Generate Second Invoice
        $inv2 = $repo->create([
            'user_id' => $user->id,
            'total_amount' => 100000,
            'status' => 'unpaid',
            'issue_date' => now(),
            'due_date' => now()->addDays(7),
        ]);

        $expected2 = "SIM-LP/{$year}/{$month}/00002";
        $this->assertEquals($expected2, $inv2->invoice_number);
    }

    public function test_geofencing_service()
    {
        $service = new GeofencingService();
        
        $student = User::factory()->create([
            'latitude' => -6.200000,
            'longitude' => 106.816666,
        ]);
        
        $tentor = User::factory()->create();

        // 1. Inside Range (e.g., 50m)
        // 0.00045 degrees is roughly 50m
        $result = $service->verifyLocation($tentor, $student, -6.200000, 106.816666 + 0.00045);
        $this->assertTrue($result['allowed']);

        // 2. Outside Range (e.g., 200m)
        // 0.002 degrees is roughly 220m
        $result = $service->verifyLocation($tentor, $student, -6.200000, 106.816666 + 0.002);
        $this->assertFalse($result['allowed']);
        
        // Verify Fraud Log
        $this->assertDatabaseHas('fraud_logs', [
            'user_id' => $tentor->id,
            'type' => 'geofence_mismatch',
        ]);
    }

    public function test_dispute_observer()
    {
        // Mock Escrow Service to avoid complex setup
        $this->mock(\App\Services\EscrowService::class, function ($mock) {
            $mock->shouldReceive('disputeFunds')->once();
        });

        $student = User::factory()->create();
        $tentor = User::factory()->create();
        
        // Create Wallet for Tentor
        Wallet::create([
            'user_id' => $tentor->id,
            'balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
        ]);

        $subject = Subject::create(['name' => 'Math', 'level' => 'SMA']);
        
        // Create Session
        $session = TutoringSession::create([
            'student_id' => $student->id,
            'tentor_id' => $tentor->id,
            'subject_id' => $subject->id,
            'status' => 'ongoing',
            'scheduled_at' => now(),
            'duration_minutes' => 60,
        ]);

        // Update with Low Rating
        $session->rating = 2;
        $session->save();

        // Check Status Changed
        $this->assertEquals('disputed', $session->fresh()->status);
    }
}
