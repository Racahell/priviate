<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleSlot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subject_id',
        'student_id',
        'tentor_id',
        'start_at',
        'end_at',
        'status',
        'created_by',
        'locked_at',
        'lock_expires_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'locked_at' => 'datetime',
        'lock_expires_at' => 'datetime',
    ];
}
