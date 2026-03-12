<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendance_records')) {
            return;
        }

        Schema::table('attendance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_records', 'teacher_photo_path')) {
                $table->string('teacher_photo_path')->nullable()->after('student_lng');
            }
            if (!Schema::hasColumn('attendance_records', 'student_photo_path')) {
                $table->string('student_photo_path')->nullable()->after('teacher_photo_path');
            }
            if (!Schema::hasColumn('attendance_records', 'teacher_validated_student')) {
                $table->boolean('teacher_validated_student')->default(false)->after('student_photo_path');
            }
            if (!Schema::hasColumn('attendance_records', 'student_validated_teacher')) {
                $table->boolean('student_validated_teacher')->default(false)->after('teacher_validated_student');
            }
            if (!Schema::hasColumn('attendance_records', 'teacher_validated_at')) {
                $table->timestamp('teacher_validated_at')->nullable()->after('student_validated_teacher');
            }
            if (!Schema::hasColumn('attendance_records', 'student_validated_at')) {
                $table->timestamp('student_validated_at')->nullable()->after('teacher_validated_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('attendance_records')) {
            return;
        }

        Schema::table('attendance_records', function (Blueprint $table) {
            foreach ([
                'teacher_photo_path',
                'student_photo_path',
                'teacher_validated_student',
                'student_validated_teacher',
                'teacher_validated_at',
                'student_validated_at',
            ] as $column) {
                if (Schema::hasColumn('attendance_records', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
