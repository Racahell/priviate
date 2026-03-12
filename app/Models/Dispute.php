<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dispute extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_OPEN = 'DISPUTE_OPEN';
    public const STATUS_IN_REVIEW_L1 = 'IN_REVIEW_L1';
    public const STATUS_IN_REVIEW_ADMIN = 'IN_REVIEW_ADMIN';
    public const STATUS_RESOLVED = 'RESOLVED';

    public const PRIORITY_LOW = 'LOW';
    public const PRIORITY_MEDIUM = 'MEDIUM';
    public const PRIORITY_HIGH = 'HIGH';
    public const PRIORITY_CRITICAL = 'CRITICAL';

    public const ALLOWED_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_IN_REVIEW_L1,
        self::STATUS_IN_REVIEW_ADMIN,
        self::STATUS_RESOLVED,
    ];

    public const ALLOWED_PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH,
        self::PRIORITY_CRITICAL,
    ];

    protected $fillable = [
        'tutoring_session_id',
        'created_by',
        'source_role',
        'reason',
        'description',
        'status',
        'priority',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(TutoringSession::class, 'tutoring_session_id');
    }

    public function actions()
    {
        return $this->hasMany(DisputeAction::class);
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = strtoupper((string) $value);
    }

    public function setPriorityAttribute($value): void
    {
        $this->attributes['priority'] = strtoupper((string) $value);
    }
}
