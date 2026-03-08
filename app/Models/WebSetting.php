<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_name',
        'logo_url',
        'address',
        'contact_email',
        'contact_phone',
        'extra',
    ];

    protected $casts = [
        'extra' => 'array',
    ];
}
