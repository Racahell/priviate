<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RescheduleRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tutoring_session_id',
        'requested_by',
        'requested_start_at',
        'requested_end_at',
        'status',
        'reason',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'requested_start_at' => 'datetime',
        'requested_end_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(TutoringSession::class, 'tutoring_session_id');
    }
}
