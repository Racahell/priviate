<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'mode',
        'file_path',
        'created_by',
        'file_size',
        'checksum_hash',
        'note',
        'status',
    ];
}
