<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_date',
        'coa_id',
        'debit',
        'credit',
        'description',
        'reference_type',
        'reference_id',
        'accounting_period_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function coa()
    {
        return $this->belongsTo(Coa::class);
    }

    public function accountingPeriod()
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
