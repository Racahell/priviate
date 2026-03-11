<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'tutoring_session_id',
        'teacher_id',
        'summary',
        'homework',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];
}
