<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalCostEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'cost_date',
        'category',
        'amount',
        'description',
        'created_by',
    ];

    protected $casts = [
        'cost_date' => 'date',
        'amount' => 'decimal:2',
    ];
}
