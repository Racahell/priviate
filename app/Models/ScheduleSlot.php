<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleSlot extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_OPEN = 'OPEN';
    public const STATUS_CLOSED = 'CLOSED';
    public const STATUS_LOCKED = 'LOCKED';
    public const STATUS_BOOKED = 'BOOKED';
    public const STATUS_ASSIGNED = 'ASSIGNED';

    public const ALLOWED_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_CLOSED,
        self::STATUS_LOCKED,
        self::STATUS_BOOKED,
        self::STATUS_ASSIGNED,
    ];

    protected $fillable = [
        'name',
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

    public function tutoringSessions()
    {
        return $this->hasMany(TutoringSession::class, 'schedule_slot_id');
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = strtoupper((string) $value);
    }
}
