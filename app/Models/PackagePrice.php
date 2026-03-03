<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackagePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'price',
        'start_date',
        'end_date',
        'is_active',
    ];
}
