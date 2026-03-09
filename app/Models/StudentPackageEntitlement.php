<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPackageEntitlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'invoice_id',
        'weekly_quota',
        'booking_weeks',
        'total_sessions',
        'used_sessions',
        'remaining_sessions',
        'is_trial',
        'status',
    ];

    protected $casts = [
        'is_trial' => 'boolean',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
