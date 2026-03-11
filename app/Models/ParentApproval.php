<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'context_type',
        'context_id',
        'status',
        'notes',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];
}
