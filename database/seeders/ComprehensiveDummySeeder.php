<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ComprehensiveDummySeeder extends Seeder
{
    private string $dummyIp = 'dummy-seeder';
    private array $tableColumns = [];

    public function run(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->ensureMinimumRoles();
        $this->seedSimpleTables();

        [$superadmin, $admin, $owner, $tentors, $students, $parents] = $this->seedUsers();
        $this->seedRoleLinks($superadmin, $admin, $owner, $tentors, $students, $parents);

        $subjectIds = $this->seedSubjects($admin->id);
        $packageIds = $this->seedPackages($admin->id);
        $coaIds = $this->seedAccountingMasters($owner->id);
        $periodIds = $this->seedPeriods($owner->id);
        $this->seedItems($admin->id);

        $this->seedProfiles($tentors, $students, $parents, $subjectIds, $admin->id);
        $walletIds = $this->seedWallets($tentors, $owner->id);
        $invoiceIds = $this->seedInvoices($students, $packageIds, $admin->id);
        $slotIds = $this->seedSchedules($students, $tentors, $subjectIds, $admin->id);
        $sessionIds = $this->seedSessions($students, $tentors, $subjectIds, $invoiceIds, $slotIds, $admin->id);

        $this->seedSessionChildren($sessionIds, $students, $tentors, $parents, $admin->id);
        $this->seedWalletChildren($walletIds, $tentors, $admin->id);
        $this->seedSecurityAndAudit($students, $tentors, $admin->id);
        $this->seedAccountingTransactions($coaIds, $periodIds, $invoiceIds, $owner->id);
        $this->seedOperationsAndReports($admin->id, $owner->id);
    }

    private function ensureMinimumRoles(): void
    {
        foreach (['finance', 'support', 'auditor', 'marketing'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }

    private function seedSimpleTables(): void
    {
        foreach (range(1, 10) as $i) {
            $this->upsert('extensions_and_schemas', ['created_at' => Carbon::create(2026, 1, $i, 8, 0, 0)], []);
            $this->upsert('functions_and_triggers', ['created_at' => Carbon::create(2026, 2, $i, 8, 0, 0)], []);

            $this->upsert('system_settings', ['key' => "dummy_setting_{$i}"], [
                'value' => "value_{$i}",
                'group' => $i <= 5 ? 'general' : 'feature_flags',
                'is_public' => $i % 2 === 0,
            ]);

            $domain = $i === 1 ? 'localhost' : "tenant{$i}.dummy.test";
            $this->upsert('tenants', ['domain' => $domain], [
                'name' => "Dummy Tenant {$i}",
                'logo_url' => "https://example.test/logo-{$i}.png",
                'primary_color' => sprintf('#%06X', 0x111111 * $i),
                'footer_content' => "Footer tenant {$i}",
                'is_active' => true,
            ]);

            $this->upsert('web_settings', ['contact_email' => "setting{$i}@dummy.test"], [
                'site_name' => "Dummy Web {$i}",
                'logo_url' => "https://example.test/web-logo-{$i}.png",
                'address' => "Jl. Dummy Web {$i}, Jakarta",
                'manager_name' => "Manager {$i}",
                'contact_phone' => "08123{$i}56789",
                'extra' => json_encode(['theme' => "theme_{$i}"]),
            ]);
        }
    }

    private function seedUsers(): array
    {
        $superadmin = $this->upsertUser('superadmin@dummy.test', [
            'name' => 'Dummy Superadmin',
            'phone' => '081111111111',
            'address' => 'Jl. Superadmin Dummy No. 1',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'postal_code' => '10110',
            'is_active' => true,
        ]);

        $admin = $this->upsertUser('admin@dummy.test', [
            'name' => 'Dummy Admin',
            'phone' => '082222222222',
            'address' => 'Jl. Admin Dummy No. 2',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'postal_code' => '10120',
            'is_active' => true,
        ]);

        $owner = $this->upsertUser('owner@dummy.test', [
            'name' => 'Dummy Owner',
            'phone' => '083333333333',
            'address' => 'Jl. Owner Dummy No. 3',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'postal_code' => '40111',
            'is_active' => true,
        ]);

        $tentors = [];
        $students = [];
        $parents = [];
        foreach (range(1, 10) as $i) {
            $parents[] = $this->upsertUser("parent{$i}@dummy.test", [
                'name' => "Dummy Parent {$i}",
                'phone' => "085700000{$i}",
                'address' => "Jl. Orang Tua {$i} No. {$i}",
                'city' => 'Batam',
                'province' => 'Kepulauan Riau',
                'postal_code' => sprintf('2941%1d', $i),
                'is_active' => true,
            ]);

            $students[] = $this->upsertUser("student{$i}@dummy.test", [
                'name' => "Dummy Student {$i}",
                'code' => sprintf('SIS-DUMMY-%03d', $i),
                'parent_id' => $parents[$i - 1]->id,
                'phone' => "085800000{$i}",
                'address' => "Jl. Melati {$i} No. {$i}",
                'city' => 'Batam',
                'province' => 'Kepulauan Riau',
                'postal_code' => sprintf('2943%1d', $i),
                'latitude' => -1.10000000 + ($i / 1000),
                'longitude' => 104.00000000 + ($i / 1000),
                'is_active' => true,
            ]);

            if (Schema::hasColumn('users', 'location_notes')) {
                DB::table('users')->where('id', $students[$i - 1]->id)->update([
                    'location_notes' => "Rumah dummy {$i}, pagar hitam, dekat masjid.",
                ]);
            }

            $tentors[] = $this->upsertUser("tentor{$i}@dummy.test", [
                'name' => "Dummy Tentor {$i}",
                'phone' => "086900000{$i}",
                'address' => "Jl. Tentor {$i} No. {$i}",
                'city' => 'Batam',
                'province' => 'Kepulauan Riau',
                'postal_code' => sprintf('2945%1d', $i),
                'latitude' => -1.20000000 + ($i / 1000),
                'longitude' => 104.10000000 + ($i / 1000),
                'is_active' => true,
            ]);
        }

        return [$superadmin, $admin, $owner, collect($tentors), collect($students), collect($parents)];
    }

    private function seedRoleLinks(User $superadmin, User $admin, User $owner, $tentors, $students, $parents): void
    {
        $superadmin->syncRoles(['superadmin']);
        $admin->syncRoles(['admin']);
        $owner->syncRoles(['owner']);
        $tentors->each(fn (User $user) => $user->syncRoles(['tentor']));
        $students->each(fn (User $user) => $user->syncRoles(['siswa']));
        $parents->each(fn (User $user) => $user->syncRoles(['orang_tua']));

        $permissions = Permission::query()->orderBy('id')->get()->values();
        $users = collect([$superadmin, $admin, $owner])->concat($tentors)->concat($students)->take(10)->values();

        foreach ($users as $index => $user) {
            $permission = $permissions[$index % max(1, $permissions->count())] ?? null;
            if ($permission) {
                $user->givePermissionTo($permission);
            }
        }
    }

    private function seedSubjects(int $actorId): array
    {
        $ids = [];
        $levels = ['SD', 'SMP', 'SMA', 'SMK', 'UNIV'];
        foreach (range(1, 10) as $i) {
            $name = "Dummy Subject {$i}";
            $this->upsert('subjects', ['name' => $name], [
                'level' => $levels[$i % count($levels)],
                'description' => "Deskripsi mapel dummy {$i}",
                'is_active' => true,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $ids[] = (int) DB::table('subjects')->where('name', $name)->value('id');
        }

        return $ids;
    }

    private function seedPackages(int $actorId): array
    {
        $ids = [];
        foreach (range(1, 10) as $i) {
            $name = "Dummy Package {$i}";
            $this->upsert('packages', ['name' => $name], [
                'description' => "Paket dummy {$i}",
                'is_active' => true,
                'trial_enabled' => $i === 1,
                'trial_limit' => $i === 1 ? 1 : 0,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $packageId = (int) DB::table('packages')->where('name', $name)->value('id');
            $ids[] = $packageId;

            $this->upsert('package_prices', ['package_id' => $packageId], [
                'price' => 150000 + ($i * 25000),
                'start_date' => Carbon::create(2026, 1, 1)->toDateString(),
                'end_date' => Carbon::create(2026, 12, 31)->toDateString(),
                'is_active' => true,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->upsert('package_quotas', ['package_id' => $packageId], [
                'quota' => 4 + $i,
                'used_quota' => max(0, $i - 1),
                'is_active' => true,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        }

        return $ids;
    }

    private function seedAccountingMasters(int $actorId): array
    {
        $ids = [];
        $types = [
            ['asset', 'debit'],
            ['liability', 'credit'],
            ['equity', 'credit'],
            ['revenue', 'credit'],
            ['expense', 'debit'],
        ];

        foreach (range(1, 10) as $i) {
            $pair = $types[($i - 1) % count($types)];
            $code = sprintf('D%03d', $i);
            $this->upsert('coas', ['code' => $code], [
                'name' => "Dummy COA {$i}",
                'type' => $pair[0],
                'normal_balance' => $pair[1],
                'is_active' => true,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $ids[] = (int) DB::table('coas')->where('code', $code)->value('id');
        }

        return $ids;
    }

    private function seedPeriods(int $actorId): array
    {
        $accounting = [];
        foreach (range(1, 10) as $i) {
            $start = Carbon::create(2026, $i, 1);
            $end = $start->copy()->endOfMonth();
            $this->upsert('accounting_periods', ['name' => $start->format('F Y')], [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'is_closed' => $i < 4,
                'closed_at' => $i < 4 ? $end->copy()->endOfDay() : null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $accounting[] = (int) DB::table('accounting_periods')->where('name', $start->format('F Y'))->value('id');

            $this->upsert('payroll_periods', ['name' => "Payroll {$start->format('M Y')}"], [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => $i < 4 ? 'CLOSED' : 'OPEN',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        }

        return $accounting;
    }

    private function seedItems(int $actorId): void
    {
        foreach (range(1, 10) as $i) {
            $sku = sprintf('DUMMY-ITEM-%03d', $i);
            $this->upsert('items', ['sku' => $sku], [
                'name' => "Dummy Item {$i}",
                'description' => "Inventori dummy {$i}",
                'price' => 10000 * $i,
                'stock' => 20 + $i,
                'is_active' => true,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        }
    }

    private function seedProfiles($tentors, $students, $parents, array $subjectIds, int $actorId): void
    {
        foreach (range(1, 10) as $i) {
            $tentor = $tentors[$i - 1];
            $student = $students[$i - 1];
            $parent = $parents[$i - 1];

            $this->upsert('tentor_profiles', ['user_id' => $tentor->id], [
                'bio' => "Bio tentor dummy {$i}",
                'education' => "S1 Pendidikan {$i}",
                'experience_years' => $i,
                'domicile' => 'Batam',
                'teaching_mode' => $i % 2 === 0 ? 'offline' : 'online',
                'offline_coverage' => 'Batam Center',
                'verification_status' => 'APPROVED',
                'verification_notes' => "Verified dummy {$i}",
                'rating' => min(5, 4 + ($i / 10)),
                'total_sessions' => $i * 3,
                'fraud_score' => 0,
                'penalty_count' => 0,
                'is_verified' => true,
                'bank_name' => 'BCA',
                'bank_account_number' => sprintf('12345000%02d', $i),
                'bank_account_holder' => $tentor->name,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $profileId = (int) DB::table('tentor_profiles')->where('user_id', $tentor->id)->value('id');

            $this->upsert('tentor_skills', ['tentor_profile_id' => $profileId, 'subject_id' => $subjectIds[$i - 1]], [
                'hourly_rate' => 75000 + ($i * 5000),
                'is_verified' => true,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->upsert('tentor_availabilities', ['tentor_profile_id' => $profileId, 'day_of_week' => $i % 7], [
                'start_time' => '16:00:00',
                'end_time' => '20:00:00',
                'is_available' => true,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->upsert('siswa_profiles', ['user_id' => $student->id], [
                'grade_level' => 'Kelas ' . (6 + $i),
                'school_name' => "Sekolah Dummy {$i}",
                'parent_name' => $parent->name,
                'parent_phone' => $parent->phone,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->upsert('student_tutor_monthly_assignments', [
                'student_id' => $student->id,
                'subject_id' => $subjectIds[$i - 1],
                'month_key' => '2026-03',
            ], [
                'tentor_id' => $tentor->id,
                'is_active' => true,
            ]);
        }
    }

    private function seedWallets($tentors, int $actorId): array
    {
        $ids = [];
        foreach (range(1, 10) as $i) {
            $tentor = $tentors[$i - 1];
            $this->upsert('wallets', ['user_id' => $tentor->id], [
                'balance' => 500000 + ($i * 100000),
                'held_balance' => 50000 + ($i * 10000),
                'pin' => Hash::make('123456'),
                'is_active' => true,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $ids[] = (int) DB::table('wallets')->where('user_id', $tentor->id)->value('id');
        }

        return $ids;
    }

    private function seedInvoices($students, array $packageIds, int $actorId): array
    {
        $ids = [];
        foreach (range(1, 10) as $i) {
            $student = $students[$i - 1];
            $packageId = $packageIds[$i - 1];
            $invoiceNumber = sprintf('INV-DUMMY-%03d', $i);
            $amount = 150000 + ($i * 25000);

            $this->upsert('invoices', ['invoice_number' => $invoiceNumber], [
                'user_id' => $student->id,
                'total_amount' => $amount,
                'status' => $i % 3 === 0 ? 'paid' : 'unpaid',
                'issue_date' => Carbon::create(2026, 3, $i)->toDateString(),
                'due_date' => Carbon::create(2026, 3, min(28, $i + 7))->toDateString(),
                'notes' => "Draft invoice package #{$packageId} Dummy Package {$i}",
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $invoiceId = (int) DB::table('invoices')->where('invoice_number', $invoiceNumber)->value('id');
            $ids[] = $invoiceId;

            $this->upsert('invoice_items', ['invoice_id' => $invoiceId], [
                'description' => "Paket #{$packageId}: Dummy Package {$i}",
                'quantity' => 1,
                'unit_price' => $amount,
                'amount' => $amount,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->upsert('payments', ['transaction_id' => "TX-DUMMY-{$i}"], [
                'invoice_id' => $invoiceId,
                'payment_method' => 'bank_transfer',
                'amount' => $amount,
                'status' => $i % 3 === 0 ? 'success' : 'pending',
                'paid_at' => $i % 3 === 0 ? Carbon::create(2026, 3, $i, 10, 0, 0) : null,
                'proof_url' => "https://example.test/proof-{$i}.png",
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        }

        return $ids;
    }

    private function seedSchedules($students, $tentors, array $subjectIds, int $actorId): array
    {
        $ids = [];
        foreach (range(1, 10) as $i) {
            $start = Carbon::create(2026, 4, $i, 18, 0, 0);
            $end = $start->copy()->addMinutes(90);
            $this->upsert('schedule_slots', ['start_at' => $start, 'tentor_id' => $tentors[$i - 1]->id], [
                'subject_id' => $subjectIds[$i - 1],
                'student_id' => $students[$i - 1]->id,
                'end_at' => $end,
                'status' => 'ASSIGNED',
                'locked_at' => null,
                'lock_expires_at' => null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $slotId = (int) DB::table('schedule_slots')
                ->where('tentor_id', $tentors[$i - 1]->id)
                ->where('start_at', $start)
                ->value('id');
            $ids[] = $slotId;

            $this->upsert('schedule_assignments', ['schedule_slot_id' => $slotId], [
                'assigned_by' => $actorId,
                'tentor_id' => $tentors[$i - 1]->id,
                'assignment_mode' => $i % 2 === 0 ? 'MANUAL' : 'AUTO',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        }

        return $ids;
    }

    private function seedSessions($students, $tentors, array $subjectIds, array $invoiceIds, array $slotIds, int $actorId): array
    {
        $ids = [];
        $statuses = ['booked', 'ongoing', 'completed', 'pending', 'booked', 'completed', 'booked', 'ongoing', 'completed', 'booked'];
        foreach (range(1, 10) as $i) {
            $scheduled = Carbon::create(2026, 4, $i, 18, 0, 0);
            $this->upsert('tutoring_sessions', ['schedule_slot_id' => $slotIds[$i - 1]], [
                'student_id' => $students[$i - 1]->id,
                'tentor_id' => $tentors[$i - 1]->id,
                'primary_tentor_id' => $tentors[$i - 1]->id,
                'is_substitute' => false,
                'subject_id' => $subjectIds[$i - 1],
                'invoice_id' => $invoiceIds[$i - 1],
                'scheduled_at' => $scheduled,
                'duration_minutes' => 90,
                'delivery_mode' => $i % 2 === 0 ? 'offline' : 'online',
                'status' => $statuses[$i - 1],
                'locked_at' => null,
                'locked_expires_at' => null,
                'journal_content' => "Jurnal sesi dummy {$i}",
                'rating' => $i % 5 + 1,
                'review' => "Review dummy {$i}",
                'auto_completed_at' => $statuses[$i - 1] === 'completed' ? $scheduled->copy()->addMinutes(90) : null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $ids[] = (int) DB::table('tutoring_sessions')->where('schedule_slot_id', $slotIds[$i - 1])->value('id');
        }

        return $ids;
    }

    private function seedSessionChildren(array $sessionIds, $students, $tentors, $parents, int $actorId): void
    {
        foreach (range(1, 10) as $i) {
            $sessionId = $sessionIds[$i - 1];
            $session = DB::table('tutoring_sessions')->where('id', $sessionId)->first();
            $scheduled = Carbon::parse($session->scheduled_at);

            $this->upsert('attendance_records', ['tutoring_session_id' => $sessionId], [
                'teacher_id' => $tentors[$i - 1]->id,
                'student_id' => $students[$i - 1]->id,
                'teacher_present' => true,
                'student_present' => $i % 4 !== 0,
                'teacher_lat' => -1.2 + ($i / 1000),
                'teacher_lng' => 104.1 + ($i / 1000),
                'student_lat' => -1.1 + ($i / 1000),
                'student_lng' => 104.0 + ($i / 1000),
                'location_status' => 'ALLOW',
                'attendance_at' => $scheduled->copy()->addMinutes(5),
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->upsert('material_reports', ['tutoring_session_id' => $sessionId], [
                'teacher_id' => $tentors[$i - 1]->id,
                'summary' => "Ringkasan materi dummy {$i}",
                'homework' => "PR dummy {$i}",
                'submitted_at' => $scheduled->copy()->addMinutes(95),
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->upsert('disputes', ['tutoring_session_id' => $sessionId], [
                'created_by' => $students[$i - 1]->id,
                'source_role' => 'siswa',
                'reason' => 'DUMMY_REASON_' . $i,
                'description' => "Dispute dummy {$i}",
                'status' => $i % 2 === 0 ? 'RESOLVED' : 'DISPUTE_OPEN',
                'priority' => $i % 3 === 0 ? 'HIGH' : 'MEDIUM',
                'resolved_at' => $i % 2 === 0 ? $scheduled->copy()->addDay() : null,
                'resolved_by' => $i % 2 === 0 ? $actorId : null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $disputeId = (int) DB::table('disputes')->where('tutoring_session_id', $sessionId)->value('id');

            $this->upsert('dispute_actions', ['dispute_id' => $disputeId], [
                'actor_id' => $actorId,
                'action' => $i % 2 === 0 ? 'RESOLVE' : 'OPEN',
                'notes' => "Aksi dispute dummy {$i}",
                'metadata' => json_encode(['session_id' => $sessionId]),
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $payrollPeriodId = (int) DB::table('payroll_periods')->orderBy('id')->skip($i - 1)->value('id');
            $this->upsert('teacher_payouts', ['reference_number' => "PAYOUT-DUMMY-{$i}"], [
                'payroll_period_id' => $payrollPeriodId,
                'teacher_id' => $tentors[$i - 1]->id,
                'gross_amount' => 100000 + ($i * 10000),
                'deduction_amount' => 5000,
                'net_amount' => 95000 + ($i * 10000),
                'status' => $i % 2 === 0 ? 'PAID' : 'PENDING',
                'paid_at' => $i % 2 === 0 ? Carbon::create(2026, 4, $i, 21, 0, 0) : null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->upsert('parent_approvals', ['context_type' => 'TutoringSession', 'context_id' => $sessionId], [
                'parent_id' => $parents[$i - 1]->id,
                'status' => $i % 2 === 0 ? 'APPROVED' : 'PENDING',
                'notes' => "Approval dummy {$i}",
                'approved_at' => $i % 2 === 0 ? Carbon::create(2026, 4, $i, 17, 0, 0) : null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->upsert('reschedule_requests', ['tutoring_session_id' => $sessionId], [
                'requested_by' => $parents[$i - 1]->id,
                'requested_start_at' => $scheduled->copy()->addDays(7),
                'requested_end_at' => $scheduled->copy()->addDays(7)->addMinutes(90),
                'status' => $i % 2 === 0 ? 'APPROVED' : 'PENDING',
                'reason' => "Permintaan reschedule dummy {$i}",
                'approved_at' => $i % 2 === 0 ? $scheduled->copy()->addDays(2) : null,
                'approved_by' => $i % 2 === 0 ? $actorId : null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        }
    }

    private function seedWalletChildren(array $walletIds, $tentors, int $actorId): void
    {
        foreach (range(1, 10) as $i) {
            $walletId = $walletIds[$i - 1];
            $payoutId = (int) DB::table('teacher_payouts')->where('reference_number', "PAYOUT-DUMMY-{$i}")->value('id');

            $this->upsert('withdrawals', ['wallet_id' => $walletId], [
                'amount' => 50000 + ($i * 5000),
                'bank_name' => 'BCA',
                'account_number' => sprintf('99887766%02d', $i),
                'account_holder' => $tentors[$i - 1]->name,
                'status' => $i % 2 === 0 ? 'completed' : 'requested',
                'admin_note' => "Withdrawal dummy {$i}",
                'processed_at' => $i % 2 === 0 ? Carbon::create(2026, 4, $i, 22, 0, 0) : null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $withdrawalId = (int) DB::table('withdrawals')->where('wallet_id', $walletId)->value('id');

            $this->upsert('wallet_transactions', ['wallet_id' => $walletId, 'description' => "Wallet transaction dummy {$i}"], [
                'type' => $i % 2 === 0 ? 'payout' : 'hold',
                'amount' => 50000 + ($i * 5000),
                'balance_before' => 400000 + ($i * 100000),
                'balance_after' => 450000 + ($i * 100000),
                'status' => 'success',
                'reference_type' => $i % 2 === 0 ? 'teacher_payout' : 'withdrawal',
                'reference_id' => $i % 2 === 0 ? $payoutId : $withdrawalId,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        }
    }

    private function seedSecurityAndAudit($students, $tentors, int $actorId): void
    {
        foreach (range(1, 10) as $i) {
            $user = $students[$i - 1];
            $tentor = $tentors[$i - 1];

            $this->upsert('user_consents', ['user_id' => $user->id, 'tos_version' => "v1.{$i}"], [
                'ip_address' => "127.0.0.{$i}",
                'user_agent' => "Dummy Browser {$i}",
                'agreed_at' => Carbon::create(2026, 3, $i, 8, 0, 0),
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->upsert('registration_email_verifications', ['email' => $user->email], [
                'token' => hash('sha256', "dummy-token-{$i}"),
                'expires_at' => Carbon::create(2026, 3, $i, 9, 0, 0),
                'used_at' => Carbon::create(2026, 3, $i, 8, 30, 0),
                'sent_ip' => "127.0.0.{$i}",
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->upsert('login_events', ['session_id' => "dummy-session-{$i}"], [
                'user_id' => $user->id,
                'role' => 'siswa',
                'status' => 'LOGIN_SUCCESS',
                'ip_address' => "127.0.0.{$i}",
                'latitude' => -1.1 + ($i / 1000),
                'longitude' => 104.0 + ($i / 1000),
                'location_status' => 'ALLOW',
                'device_fingerprint' => "dummy-device-{$i}",
                'browser' => 'Chrome Dummy',
                'os' => 'Windows Dummy',
                'anomaly_flag' => false,
                'metadata' => json_encode(['source' => 'dummy-seeder']),
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->upsert('security_events', ['event_type' => "DUMMY_EVENT_{$i}", 'user_id' => $user->id], [
                'severity' => $i % 3 === 0 ? 'HIGH' : 'LOW',
                'description' => "Security event dummy {$i}",
                'ip_address' => "127.0.0.{$i}",
                'metadata' => json_encode(['session' => "dummy-session-{$i}"]),
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->upsert('fraud_logs', ['user_id' => $tentor->id, 'type' => "dummy_fraud_{$i}"], [
                'description' => "Fraud log dummy {$i}",
                'severity_score' => 10 + $i,
                'metadata' => json_encode(['teacher_id' => $tentor->id]),
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->upsert('idempotency_keys', ['key' => "dummy-idempotency-{$i}"], [
                'user_id' => $user->id,
                'path' => '/ops/dummy',
                'method' => 'POST',
                'response_code' => 200,
                'response_body' => json_encode(['ok' => true, 'idx' => $i]),
                'expires_at' => Carbon::create(2026, 5, $i, 23, 59, 59),
            ]);

            $this->upsert('password_reset_requests', ['email' => $user->email], [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'channel' => 'EMAIL',
                'otp_code' => sprintf('%06d', 100000 + $i),
                'expires_at' => Carbon::create(2026, 5, $i, 12, 0, 0),
                'used_at' => null,
                'request_ip' => "127.0.0.{$i}",
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->upsert('password_reset_tokens', ['email' => $user->email], [
                'token' => hash('sha256', "dummy-reset-{$i}"),
                'created_at' => Carbon::create(2026, 5, $i, 11, 0, 0),
            ], false);

            $this->upsert('audit_logs', ['url' => "/dummy/url/{$i}", 'event' => "DUMMY_AUDIT_{$i}"], [
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'old_values' => json_encode(['status' => 'old']),
                'new_values' => json_encode(['status' => 'new']),
                'ip_address' => "127.0.0.{$i}",
                'user_agent' => 'Dummy Agent',
                'session_id' => "dummy-session-{$i}",
                'role' => 'siswa',
                'action' => 'UPDATE_PROFILE',
                'location_status' => 'ALLOW',
                'latitude' => -1.1 + ($i / 1000),
                'longitude' => 104.0 + ($i / 1000),
                'device_fingerprint' => "dummy-device-{$i}",
                'browser' => 'Chrome Dummy',
                'os' => 'Windows Dummy',
                'anomaly_flag' => false,
                'checksum_signature' => hash('sha256', "audit-{$i}"),
            ], false);

            $this->upsert('history_edits', ['model_type' => User::class, 'model_id' => $user->id, 'field' => 'phone'], [
                'user_id' => $actorId,
                'old_value' => '08123',
                'new_value' => $user->phone,
                'reason' => "Dummy history {$i}",
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        }
    }

    private function seedAccountingTransactions(array $coaIds, array $periodIds, array $invoiceIds, int $actorId): void
    {
        $tenantId = (int) DB::table('tenants')->where('domain', 'localhost')->value('id');

        foreach (range(1, 10) as $i) {
            $transactionDate = Carbon::create(2026, $i, 10);
            $journalDescription = "Dummy Journal {$i}";

            $this->upsert('journal_entries', ['description' => $journalDescription], [
                'transaction_date' => $transactionDate->toDateString(),
                'reference_type' => 'invoice',
                'reference_id' => $invoiceIds[$i - 1],
                'currency' => 'IDR',
                'is_locked' => $i < 3,
                'tenant_id' => $tenantId,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $journalEntryId = (int) DB::table('journal_entries')->where('description', $journalDescription)->value('id');

            $this->upsert('journal_items', ['journal_entry_id' => $journalEntryId], [
                'coa_id' => $coaIds[$i - 1],
                'debit' => 100000 + ($i * 10000),
                'credit' => 0,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->upsert('financial_ledgers', ['description' => "Dummy Ledger {$i}"], [
                'transaction_date' => $transactionDate->toDateString(),
                'coa_id' => $coaIds[$i - 1],
                'debit' => 100000 + ($i * 10000),
                'credit' => 0,
                'reference_type' => 'invoice',
                'reference_id' => $invoiceIds[$i - 1],
                'accounting_period_id' => $periodIds[$i - 1],
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        }
    }

    private function seedOperationsAndReports(int $adminId, int $ownerId): void
    {
        foreach (range(1, 10) as $i) {
            $backupPath = "backups/dummy-backup-{$i}.zip";
            $this->upsert('backup_jobs', ['file_path' => $backupPath], [
                'type' => 'DB',
                'mode' => $i % 2 === 0 ? 'FULL' : 'UPDATE',
                'created_by' => $adminId,
                'file_size' => 1024 * $i,
                'checksum_hash' => hash('sha256', $backupPath),
                'note' => "Backup dummy {$i}",
                'status' => 'CREATED',
                'updated_by' => $adminId,
            ]);
            $backupId = (int) DB::table('backup_jobs')->where('file_path', $backupPath)->value('id');

            $this->upsert('restore_jobs', ['backup_job_id' => $backupId], [
                'mode' => $i % 2 === 0 ? 'DISASTER' : 'PARTIAL',
                'requested_by' => $adminId,
                'status' => $i % 2 === 0 ? 'DONE' : 'PENDING',
                'diff_preview' => json_encode(['tables' => ['users', 'invoices']]),
                'reason' => "Restore dummy {$i}",
                'executed_at' => $i % 2 === 0 ? Carbon::create(2026, 6, $i, 1, 0, 0) : null,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);

            $importType = "users_batch_{$i}";
            $this->upsert('import_jobs', ['type' => $importType], [
                'requested_by' => $adminId,
                'status' => 'SUCCESS',
                'total_rows' => 10,
                'success_rows' => 10,
                'failed_rows' => 0,
                'error_summary' => null,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);
            $importId = (int) DB::table('import_jobs')->where('type', $importType)->value('id');

            $this->upsert('import_job_details', ['import_job_id' => $importId], [
                'row_number' => $i,
                'status' => 'SUCCESS',
                'message' => "Import dummy {$i}",
                'payload' => json_encode(['row' => $i]),
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);

            $this->upsert('financial_reports', ['report_date' => Carbon::create(2026, $i, 28)->toDateString()], [
                'revenue' => 1000000 + ($i * 100000),
                'teacher_payout' => 400000 + ($i * 50000),
                'refund' => 10000 * $i,
                'operational_cost' => 50000 * $i,
                'escrow_outstanding' => 25000 * $i,
                'net_profit' => 500000 + ($i * 40000),
                'created_by' => $ownerId,
                'updated_by' => $ownerId,
            ]);

            $this->upsert('operational_cost_entries', [
                'cost_date' => Carbon::create(2026, $i, 20)->toDateString(),
                'category' => "Dummy Cost {$i}",
            ], [
                'amount' => 25000 * $i,
                'description' => "Biaya operasional dummy {$i}",
                'created_by' => $ownerId,
                'updated_by' => $ownerId,
            ]);
        }
    }

    private function upsertUser(string $email, array $attributes): User
    {
        $payload = array_merge([
            'name' => Str::before($email, '@'),
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'is_active' => true,
            'created_ip' => $this->dummyIp,
            'updated_ip' => $this->dummyIp,
            'is_deleted' => false,
            'deleted_at' => null,
        ], $attributes);

        return User::updateOrCreate(['email' => $email], $payload);
    }

    private function upsert(string $table, array $keys, array $values, bool $withAudit = true): void
    {
        $payload = array_merge($keys, $values);
        if ($withAudit) {
            $payload = $this->withAuditColumns($table, $payload);
        }

        $payload = $this->filterPayloadForTable($table, $payload);
        $keys = $this->filterPayloadForTable($table, $keys);

        DB::table($table)->updateOrInsert($keys, $payload);
    }

    private function withAuditColumns(string $table, array $payload): array
    {
        $now = now();

        if (Schema::hasColumn($table, 'created_at') && !array_key_exists('created_at', $payload)) {
            $payload['created_at'] = $now;
        }
        if (Schema::hasColumn($table, 'updated_at') && !array_key_exists('updated_at', $payload)) {
            $payload['updated_at'] = $now;
        }
        if (Schema::hasColumn($table, 'created_ip') && !array_key_exists('created_ip', $payload)) {
            $payload['created_ip'] = $this->dummyIp;
        }
        if (Schema::hasColumn($table, 'updated_ip') && !array_key_exists('updated_ip', $payload)) {
            $payload['updated_ip'] = $this->dummyIp;
        }
        if (Schema::hasColumn($table, 'is_deleted') && !array_key_exists('is_deleted', $payload)) {
            $payload['is_deleted'] = false;
        }
        if (Schema::hasColumn($table, 'deleted_at') && !array_key_exists('deleted_at', $payload)) {
            $payload['deleted_at'] = null;
        }

        return $payload;
    }

    private function filterPayloadForTable(string $table, array $payload): array
    {
        if (!isset($this->tableColumns[$table])) {
            $this->tableColumns[$table] = Schema::getColumnListing($table);
        }

        return array_filter(
            $payload,
            fn (string $column) => in_array($column, $this->tableColumns[$table], true),
            ARRAY_FILTER_USE_KEY
        );
    }
}
