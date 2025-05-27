<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanExcess extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'loan_id',
        'employee_id',
        'amount',
        'payment_date',
        'status',
        'fee_amount',
        'remarks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
