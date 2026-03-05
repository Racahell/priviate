<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TentorSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'tentor_profile_id',
        'subject_id',
        'hourly_rate',
        'is_verified',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'is_verified' => 'boolean',
    ];

    public function tentorProfile()
    {
        return $this->belongsTo(TentorProfile::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
