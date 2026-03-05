<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Roles & Permissions are handled by Spatie Migration (2026_02_26_160727_create_permission_tables.php)

        // 5. System Settings (Feature Toggles & Whitelabeling)
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., 'app_name', 'logo_url', 'maintenance_mode'
            $table->text('value')->nullable();
            $table->string('group')->default('general'); // e.g., 'appearance', 'feature_flags'
            $table->boolean('is_public')->default(false); // Can be exposed to frontend without auth
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        // Roles & Permissions dropped by Spatie Migration
    }
};
