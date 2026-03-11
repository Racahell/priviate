<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_period_id',
        'teacher_id',
        'tutoring_session_id',
        'gross_amount',
        'deduction_amount',
        'net_amount',
        'status',
        'paid_at',
        'reference_number',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(TutoringSession::class, 'tutoring_session_id');
    }
}
