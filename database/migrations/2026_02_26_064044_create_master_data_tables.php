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
        // 1. Subjects
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'Mathematics', 'Physics'
            $table->string('level'); // e.g., 'SD', 'SMP', 'SMA', 'University'
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Tentor Profiles
        Schema::create('tentor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('bio')->nullable();
            $table->decimal('rating', 3, 2)->default(0); // 0.00 to 5.00
            $table->integer('total_sessions')->default(0);
            $table->integer('fraud_score')->default(0); // 0-100
            $table->integer('penalty_count')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_holder')->nullable();
            $table->timestamps();
        });

        // 3. Siswa Profiles
        Schema::create('siswa_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('grade_level')->nullable(); // e.g., 'Grade 10'
            $table->string('school_name')->nullable();
            $table->string('parent_name')->nullable();
            $table->string('parent_phone')->nullable();
            $table->timestamps();
        });

        // 4. Tentor Skills (Competency & Rates)
        Schema::create('tentor_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tentor_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->decimal('hourly_rate', 15, 2);
            $table->boolean('is_verified')->default(false); // Skill verification
            $table->timestamps();
        });

        // 5. Tentor Availabilities
        Schema::create('tentor_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tentor_profile_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('day_of_week'); // 0 (Sunday) - 6 (Saturday)
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });

        // 6. Tutoring Sessions (The Core Activity)
        Schema::create('tutoring_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users');
            $table->foreignId('tentor_id')->constrained('users');
            $table->foreignId('subject_id')->constrained();
            $table->foreignId('invoice_id')->nullable()->constrained(); // Linked to payment
            
            $table->dateTime('scheduled_at');
            $table->integer('duration_minutes');
            $table->enum('status', ['pending', 'booked', 'ongoing', 'completed', 'cancelled', 'disputed', 'locked'])->default('pending');
            
            // Locking mechanism
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('locked_expires_at')->nullable(); // Max 3 mins
            
            // Geofencing & Tracking
            $table->decimal('check_in_lat', 10, 8)->nullable();
            $table->decimal('check_in_lng', 11, 8)->nullable();
            $table->timestamp('check_in_time')->nullable();
            
            $table->decimal('check_out_lat', 10, 8)->nullable();
            $table->decimal('check_out_lng', 11, 8)->nullable();
            $table->timestamp('check_out_time')->nullable();
            
            // Educational Journal & Rating
            $table->text('journal_content')->nullable(); // Materi yang diajarkan
            $table->integer('rating')->nullable(); // 1-5
            $table->text('review')->nullable();
            $table->timestamp('auto_completed_at')->nullable(); // For System Auto-Complete
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutoring_sessions');
        Schema::dropIfExists('tentor_availabilities');
        Schema::dropIfExists('tentor_skills');
        Schema::dropIfExists('siswa_profiles');
        Schema::dropIfExists('tentor_profiles');
        Schema::dropIfExists('subjects');
    }
};
