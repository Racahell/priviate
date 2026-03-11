<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'held_balance',
        'pin',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'held_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'pin',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }
}
