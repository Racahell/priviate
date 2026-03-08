<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TentorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bio',
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
        'rating',
        'total_sessions',
        'fraud_score',
        'penalty_count',
        'is_verified',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'is_verified' => 'boolean',
        'experience_years' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skills()
    {
        return $this->hasMany(TentorSkill::class);
    }

    public function availabilities()
    {
        return $this->hasMany(TentorAvailability::class);
    }
}
