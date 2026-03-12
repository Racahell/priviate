<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Normalize legacy orphan records before adding strict FK constraints.
        DB::statement("UPDATE disputes d LEFT JOIN tutoring_sessions s ON d.tutoring_session_id = s.id SET d.tutoring_session_id = NULL WHERE d.tutoring_session_id IS NOT NULL AND s.id IS NULL");

        DB::statement("DELETE ar FROM attendance_records ar LEFT JOIN tutoring_sessions s ON ar.tutoring_session_id = s.id WHERE s.id IS NULL");
        DB::statement("DELETE mr FROM material_reports mr LEFT JOIN tutoring_sessions s ON mr.tutoring_session_id = s.id WHERE s.id IS NULL");
        DB::statement("DELETE rr FROM reschedule_requests rr LEFT JOIN tutoring_sessions s ON rr.tutoring_session_id = s.id WHERE s.id IS NULL");

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->foreign('tutoring_session_id', 'attendance_records_tutoring_session_fk')
                ->references('id')
                ->on('tutoring_sessions')
                ->cascadeOnDelete();
        });

        Schema::table('material_reports', function (Blueprint $table) {
            $table->foreign('tutoring_session_id', 'material_reports_tutoring_session_fk')
                ->references('id')
                ->on('tutoring_sessions')
                ->cascadeOnDelete();
        });

        Schema::table('reschedule_requests', function (Blueprint $table) {
            $table->foreign('tutoring_session_id', 'reschedule_requests_tutoring_session_fk')
                ->references('id')
                ->on('tutoring_sessions')
                ->cascadeOnDelete();
        });

        Schema::table('disputes', function (Blueprint $table) {
            $table->foreign('tutoring_session_id', 'disputes_tutoring_session_fk')
                ->references('id')
                ->on('tutoring_sessions')
                ->nullOnDelete();
        });

        Schema::table('schedule_assignments', function (Blueprint $table) {
            $table->foreign('schedule_slot_id', 'schedule_assignments_schedule_slot_fk')
                ->references('id')
                ->on('schedule_slots')
                ->cascadeOnDelete();
            $table->foreign('tentor_id', 'schedule_assignments_tentor_fk')
                ->references('id')
                ->on('users');
            $table->foreign('assigned_by', 'schedule_assignments_assigned_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('dispute_actions', function (Blueprint $table) {
            $table->foreign('dispute_id', 'dispute_actions_dispute_fk')
                ->references('id')
                ->on('disputes')
                ->cascadeOnDelete();
            $table->foreign('actor_id', 'dispute_actions_actor_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('teacher_payouts', function (Blueprint $table) {
            $table->foreign('teacher_id', 'teacher_payouts_teacher_fk')
                ->references('id')
                ->on('users');
            $table->foreign('tutoring_session_id', 'teacher_payouts_tutoring_session_fk')
                ->references('id')
                ->on('tutoring_sessions')
                ->nullOnDelete();
            $table->foreign('payroll_period_id', 'teacher_payouts_payroll_period_fk')
                ->references('id')
                ->on('payroll_periods')
                ->nullOnDelete();
        });

        Schema::table('student_package_entitlements', function (Blueprint $table) {
            $table->foreign('user_id', 'student_package_entitlements_user_fk')
                ->references('id')
                ->on('users');
            $table->foreign('package_id', 'student_package_entitlements_package_fk')
                ->references('id')
                ->on('packages')
                ->nullOnDelete();
            $table->foreign('invoice_id', 'student_package_entitlements_invoice_fk')
                ->references('id')
                ->on('invoices');
        });
    }

    public function down(): void
    {
        Schema::table('student_package_entitlements', function (Blueprint $table) {
            $table->dropForeign('student_package_entitlements_user_fk');
            $table->dropForeign('student_package_entitlements_package_fk');
            $table->dropForeign('student_package_entitlements_invoice_fk');
        });

        Schema::table('teacher_payouts', function (Blueprint $table) {
            $table->dropForeign('teacher_payouts_teacher_fk');
            $table->dropForeign('teacher_payouts_tutoring_session_fk');
            $table->dropForeign('teacher_payouts_payroll_period_fk');
        });

        Schema::table('dispute_actions', function (Blueprint $table) {
            $table->dropForeign('dispute_actions_dispute_fk');
            $table->dropForeign('dispute_actions_actor_fk');
        });

        Schema::table('schedule_assignments', function (Blueprint $table) {
            $table->dropForeign('schedule_assignments_schedule_slot_fk');
            $table->dropForeign('schedule_assignments_tentor_fk');
            $table->dropForeign('schedule_assignments_assigned_by_fk');
        });

        Schema::table('disputes', function (Blueprint $table) {
            $table->dropForeign('disputes_tutoring_session_fk');
        });

        Schema::table('reschedule_requests', function (Blueprint $table) {
            $table->dropForeign('reschedule_requests_tutoring_session_fk');
        });

        Schema::table('material_reports', function (Blueprint $table) {
            $table->dropForeign('material_reports_tutoring_session_fk');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign('attendance_records_tutoring_session_fk');
        });
    }
};
