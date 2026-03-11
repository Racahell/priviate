<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_date',
        'description',
        'reference_type',
        'reference_id',
        'currency',
        'is_locked',
        'tenant_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'is_locked' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(JournalItem::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
