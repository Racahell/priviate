<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tentor_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('tentor_profiles', 'education')) {
                $table->string('education')->nullable()->after('bio');
            }
            if (!Schema::hasColumn('tentor_profiles', 'experience_years')) {
                $table->unsignedInteger('experience_years')->nullable()->after('education');
            }
            if (!Schema::hasColumn('tentor_profiles', 'domicile')) {
                $table->string('domicile')->nullable()->after('experience_years');
            }
            if (!Schema::hasColumn('tentor_profiles', 'teaching_mode')) {
                $table->string('teaching_mode', 16)->default('online')->after('domicile');
            }
            if (!Schema::hasColumn('tentor_profiles', 'offline_coverage')) {
                $table->string('offline_coverage')->nullable()->after('teaching_mode');
            }
            if (!Schema::hasColumn('tentor_profiles', 'verification_status')) {
                $table->string('verification_status', 32)->default('PENDING_REVIEW')->after('offline_coverage');
            }
            if (!Schema::hasColumn('tentor_profiles', 'verification_notes')) {
                $table->text('verification_notes')->nullable()->after('verification_status');
            }
            if (!Schema::hasColumn('tentor_profiles', 'cv_path')) {
                $table->string('cv_path')->nullable()->after('verification_notes');
            }
            if (!Schema::hasColumn('tentor_profiles', 'diploma_path')) {
                $table->string('diploma_path')->nullable()->after('cv_path');
            }
            if (!Schema::hasColumn('tentor_profiles', 'certificate_path')) {
                $table->string('certificate_path')->nullable()->after('diploma_path');
            }
            if (!Schema::hasColumn('tentor_profiles', 'id_card_path')) {
                $table->string('id_card_path')->nullable()->after('certificate_path');
            }
            if (!Schema::hasColumn('tentor_profiles', 'profile_photo_path')) {
                $table->string('profile_photo_path')->nullable()->after('id_card_path');
            }
            if (!Schema::hasColumn('tentor_profiles', 'intro_video_url')) {
                $table->string('intro_video_url')->nullable()->after('profile_photo_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tentor_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'education',
                'experience_years',
                'domicile',
                'teaching_mode',
                'offline_coverage',
                'verification_status',
                'verification_notes',
                'cv_path',
                'diploma_path',
                'certificate_path',
                'id_card_path',
                'profile_photo_path',
                'intro_video_url',
            ]);
        });
    }
};

