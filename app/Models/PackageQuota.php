<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageQuota extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'quota',
        'used_quota',
        'is_active',
    ];
}
