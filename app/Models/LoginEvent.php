<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role',
        'status',
        'session_id',
        'ip_address',
        'latitude',
        'longitude',
        'location_status',
        'device_fingerprint',
        'browser',
        'os',
        'anomaly_flag',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'anomaly_flag' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
