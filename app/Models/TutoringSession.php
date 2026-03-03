<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TutoringSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'tentor_id',
        'subject_id',
        'invoice_id',
        'scheduled_at',
        'duration_minutes',
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
}
