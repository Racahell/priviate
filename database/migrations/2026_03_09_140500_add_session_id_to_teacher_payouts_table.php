<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_payouts', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_payouts', 'tutoring_session_id')) {
                $table->unsignedBigInteger('tutoring_session_id')->nullable()->after('teacher_id');
                $table->unique('tutoring_session_id', 'teacher_payouts_session_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_payouts', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_payouts', 'tutoring_session_id')) {
                $table->dropUnique('teacher_payouts_session_unique');
                $table->dropColumn('tutoring_session_id');
            }
        });
    }
};
