<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_tutor_monthly_assignments')) {
            Schema::create('student_tutor_monthly_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id')->index();
                $table->unsignedBigInteger('subject_id')->index();
                $table->unsignedBigInteger('tentor_id')->index();
                $table->string('month_key', 7)->index(); // YYYY-MM
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['student_id', 'subject_id', 'month_key'], 'uq_student_subject_month');
            });
        }

        if (Schema::hasTable('schedule_slots')) {
            Schema::table('schedule_slots', function (Blueprint $table) {
                if (!Schema::hasColumn('schedule_slots', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('status')->index();
                }
            });
        }

        if (Schema::hasTable('tutoring_sessions')) {
            Schema::table('tutoring_sessions', function (Blueprint $table) {
                if (!Schema::hasColumn('tutoring_sessions', 'schedule_slot_id')) {
                    $table->unsignedBigInteger('schedule_slot_id')->nullable()->after('invoice_id')->index();
                }
                if (!Schema::hasColumn('tutoring_sessions', 'primary_tentor_id')) {
                    $table->unsignedBigInteger('primary_tentor_id')->nullable()->after('tentor_id')->index();
                }
                if (!Schema::hasColumn('tutoring_sessions', 'is_substitute')) {
                    $table->boolean('is_substitute')->default(false)->after('primary_tentor_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tutoring_sessions')) {
            Schema::table('tutoring_sessions', function (Blueprint $table) {
                foreach (['schedule_slot_id', 'primary_tentor_id', 'is_substitute'] as $col) {
                    if (Schema::hasColumn('tutoring_sessions', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('schedule_slots')) {
            Schema::table('schedule_slots', function (Blueprint $table) {
                if (Schema::hasColumn('schedule_slots', 'created_by')) {
                    $table->dropColumn('created_by');
                }
            });
        }

        Schema::dropIfExists('student_tutor_monthly_assignments');
    }
};

