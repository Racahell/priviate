<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tutoring_sessions')) {
            DB::table('tutoring_sessions')->whereNotNull('status')->update([
                'status' => DB::raw('LOWER(status)'),
            ]);
        }

        if (Schema::hasTable('disputes')) {
            DB::table('disputes')->whereNotNull('status')->update([
                'status' => DB::raw('UPPER(status)'),
            ]);
            DB::table('disputes')->whereNotNull('priority')->update([
                'priority' => DB::raw('UPPER(priority)'),
            ]);
        }

        if (Schema::hasTable('reschedule_requests')) {
            DB::table('reschedule_requests')->whereNotNull('status')->update([
                'status' => DB::raw('UPPER(status)'),
            ]);
        }

        if (Schema::hasTable('teacher_payouts')) {
            DB::table('teacher_payouts')->whereNotNull('status')->update([
                'status' => DB::raw('UPPER(status)'),
            ]);
        }

        if (Schema::hasTable('schedule_slots')) {
            DB::table('schedule_slots')->whereNotNull('status')->update([
                'status' => DB::raw('UPPER(status)'),
            ]);
        }
    }

    public function down(): void
    {
        // no-op, normalization is safe and intentionally permanent
    }
};
