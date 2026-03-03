<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'level',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tentorSkills()
    {
        return $this->hasMany(TentorSkill::class);
    }

    public function sessions()
    {
        return $this->hasMany(TutoringSession::class);
    }
}
