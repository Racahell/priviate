<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Role;
use App\Models\TutoringSession;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BlueprintTest extends TestCase
{
    use RefreshDatabase;

    public function test_blueprint_flow()
    {
        // 1. Setup Roles
        $studentRole = Role::create(['name' => 'student']);
        $tentorRole = Role::create(['name' => 'tentor']);

        // 2. Create Users
        $student = User::factory()->create(['email' => 'student@example.com']);
        $student->roles()->attach($studentRole);

        $tentor = User::factory()->create(['email' => 'tentor@example.com']);
        $tentor->roles()->attach($tentorRole);

        // 3. Create Subject
        $subject = Subject::create([
            'name' => 'Advanced Physics',
            'level' => 'SMA',
            'description' => 'Physics for high school students',
        ]);

        // 4. Create Invoice
        $invoice = Invoice::create([
            'invoice_number' => 'INV-2026001',
            'user_id' => $student->id,
            'total_amount' => 150000,
            'status' => 'unpaid',
            'issue_date' => now(),
            'due_date' => now()->addDays(3),
        ]);

        // 5. Create Tutoring Session
        $session = TutoringSession::create([
            'student_id' => $student->id,
            'tentor_id' => $tentor->id,
            'subject_id' => $subject->id,
            'invoice_id' => $invoice->id,
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 90,
            'status' => 'booked',
        ]);

        // Assertions
        $this->assertTrue($student->hasRole('student'));
        $this->assertTrue($tentor->hasRole('tentor'));
        $this->assertEquals('Advanced Physics', $session->subject->name);
        $this->assertEquals($invoice->id, $session->invoice->id);
        $this->assertEquals($student->id, $session->student->id);
        $this->assertEquals($tentor->id, $session->tentor->id);
        
        // Check database records
        $this->assertDatabaseHas('roles', ['name' => 'student']);
        $this->assertDatabaseHas('tutoring_sessions', ['status' => 'booked']);
    }
}
