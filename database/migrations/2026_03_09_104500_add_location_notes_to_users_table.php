<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'location_notes')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('location_notes')->nullable()->after('longitude');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'location_notes')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('location_notes');
            });
        }
    }
};
