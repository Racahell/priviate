<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tutoring_sessions')) {
            return;
        }

        Schema::table('tutoring_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('tutoring_sessions', 'delivery_mode')) {
                $table->string('delivery_mode', 16)->default('online')->after('duration_minutes');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tutoring_sessions')) {
            return;
        }

        Schema::table('tutoring_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('tutoring_sessions', 'delivery_mode')) {
                $table->dropColumn('delivery_mode');
            }
        });
    }
};
