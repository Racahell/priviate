<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestoreJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'backup_job_id',
        'mode',
        'requested_by',
        'status',
        'diff_preview',
        'reason',
        'executed_at',
    ];

    protected $casts = [
        'diff_preview' => 'array',
        'executed_at' => 'datetime',
    ];
}
