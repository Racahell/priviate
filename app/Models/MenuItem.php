<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'route_name',
        'parent_id',
        'sort_order',
        'is_active',
    ];
}
