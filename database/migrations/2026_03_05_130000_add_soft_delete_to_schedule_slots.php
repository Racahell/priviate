<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schedule_slots') && !Schema::hasColumn('schedule_slots', 'deleted_at')) {
            Schema::table('schedule_slots', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('schedule_slots') && Schema::hasColumn('schedule_slots', 'deleted_at')) {
            Schema::table('schedule_slots', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};

