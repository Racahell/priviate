<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_package_entitlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('package_id')->nullable()->index();
            $table->unsignedBigInteger('invoice_id')->unique();
            $table->unsignedInteger('weekly_quota')->default(1);
            $table->unsignedInteger('booking_weeks')->default(1);
            $table->unsignedInteger('total_sessions')->default(0);
            $table->unsignedInteger('used_sessions')->default(0);
            $table->unsignedInteger('remaining_sessions')->default(0);
            $table->boolean('is_trial')->default(false);
            $table->string('status', 20)->default('ACTIVE')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_package_entitlements');
    }
};
