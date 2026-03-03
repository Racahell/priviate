<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'requested_by',
        'status',
        'total_rows',
        'success_rows',
        'failed_rows',
        'error_summary',
    ];
}
