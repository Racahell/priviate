<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dispute extends Model
{
    use HasFactory, SoftDeletes;

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
}
