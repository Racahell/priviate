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
        'teacher_photo_path',
        'student_photo_path',
        'teacher_validated_student',
        'student_validated_teacher',
        'teacher_validated_at',
        'student_validated_at',
        'location_status',
        'attendance_at',
    ];

    protected $casts = [
        'teacher_present' => 'boolean',
        'student_present' => 'boolean',
        'teacher_validated_student' => 'boolean',
        'student_validated_teacher' => 'boolean',
        'teacher_validated_at' => 'datetime',
        'student_validated_at' => 'datetime',
        'attendance_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(TutoringSession::class, 'tutoring_session_id');
    }
}
