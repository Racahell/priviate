<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentTutorMonthlyAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'subject_id',
        'tentor_id',
        'month_key',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

