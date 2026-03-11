<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'tutoring_session_id',
        'teacher_id',
        'student_id',
        'teacher_present',
        'student_present',
        'teacher_lat',
        'teacher_lng',
        'student_lat',
        'student_lng',
        'location_status',
        'attendance_at',
    ];

    protected $casts = [
        'teacher_present' => 'boolean',
        'student_present' => 'boolean',
        'attendance_at' => 'datetime',
    ];
}
