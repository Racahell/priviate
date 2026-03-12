<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'level',
        'class_level_id',
        'description',
        'is_active',
        'is_deleted',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function tentorSkills()
    {
        return $this->hasMany(TentorSkill::class);
    }

    public function sessions()
    {
        return $this->hasMany(TutoringSession::class);
    }

    public function classLevel()
    {
        return $this->belongsTo(ClassLevel::class);
    }
}
