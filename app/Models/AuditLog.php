<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'session_id',
        'user_id',
        'role',
        'event',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'location_status',
        'user_agent',
        'device_fingerprint',
        'browser',
        'os',
        'anomaly_flag',
        'checksum_signature',
        'url',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'anomaly_flag' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            if ((bool) env('AUDIT_LOG_IMMUTABLE', true)) {
                throw new \RuntimeException('Audit log is immutable and append-only.');
            }
        });

        static::deleting(function () {
            if ((bool) env('AUDIT_LOG_IMMUTABLE', true)) {
                throw new \RuntimeException('Audit log cannot be deleted.');
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
