<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain',
        'name',
        'logo_url',
        'primary_color',
        'footer_content',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
