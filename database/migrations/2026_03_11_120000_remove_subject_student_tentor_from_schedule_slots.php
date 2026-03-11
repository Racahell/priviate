<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('schedule_slots')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS schedule_slots_subject_id_index');
            DB::statement('DROP INDEX IF EXISTS schedule_slots_student_id_index');
            DB::statement('DROP INDEX IF EXISTS schedule_slots_tentor_id_index');
        }

        Schema::table('schedule_slots', function (Blueprint $table) {
            if (Schema::hasColumn('schedule_slots', 'subject_id')) {
                $table->dropColumn('subject_id');
            }
            if (Schema::hasColumn('schedule_slots', 'student_id')) {
                $table->dropColumn('student_id');
            }
            if (Schema::hasColumn('schedule_slots', 'tentor_id')) {
                $table->dropColumn('tentor_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('schedule_slots')) {
            return;
        }

        Schema::table('schedule_slots', function (Blueprint $table) {
            if (!Schema::hasColumn('schedule_slots', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->index();
            }
            if (!Schema::hasColumn('schedule_slots', 'student_id')) {
                $table->unsignedBigInteger('student_id')->nullable()->index();
            }
            if (!Schema::hasColumn('schedule_slots', 'tentor_id')) {
                $table->unsignedBigInteger('tentor_id')->nullable()->index();
            }
        });
    }
};
