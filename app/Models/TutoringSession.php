<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TutoringSession extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_BOOKED = 'booked';
    public const STATUS_ONGOING = 'ongoing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_DISPUTED = 'disputed';
    public const STATUS_LOCKED = 'locked';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_AUTO_COMPLETED = 'auto_completed';

    public const ACTIVE_STATUSES = [
        self::STATUS_BOOKED,
        self::STATUS_ONGOING,
        self::STATUS_LOCKED,
        self::STATUS_CONFIRMED,
    ];

    protected $fillable = [
        'student_id',
        'tentor_id',
        'primary_tentor_id',
        'is_substitute',
        'subject_id',
        'invoice_id',
        'schedule_slot_id',
        'scheduled_at',
        'duration_minutes',
        'delivery_mode',
        'status',
        'locked_at',
        'locked_expires_at',
        'check_in_lat',
        'check_in_lng',
        'check_in_time',
        'check_out_lat',
        'check_out_lng',
        'check_out_time',
        'journal_content',
        'rating',
        'review',
        'auto_completed_at',
        'is_deleted',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'locked_at' => 'datetime',
        'locked_expires_at' => 'datetime',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'auto_completed_at' => 'datetime',
        'is_substitute' => 'boolean',
        'check_in_lat' => 'decimal:8',
        'check_in_lng' => 'decimal:8',
        'check_out_lat' => 'decimal:8',
        'check_out_lng' => 'decimal:8',
        'is_deleted' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function tentor()
    {
        return $this->belongsTo(User::class, 'tentor_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function scheduleSlot()
    {
        return $this->belongsTo(ScheduleSlot::class, 'schedule_slot_id');
    }

    public function materialReport()
    {
        return $this->hasOne(MaterialReport::class, 'tutoring_session_id');
    }

    public function payout()
    {
        return $this->hasOne(TeacherPayout::class, 'tutoring_session_id');
    }

    public function attendanceRecord()
    {
        return $this->hasOne(AttendanceRecord::class, 'tutoring_session_id');
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = strtolower((string) $value);
    }
}
