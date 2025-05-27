<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'employee_id',
        'loan_number',
        'type',
        'amount',
        'interest_rate',
        'term_months',
        'start_date',
        'end_date',
        'status',
        'purpose',
        'collateral',
        'guarantor_name',
        'guarantor_contact',
        'guarantor_relationship',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'disbursed_by',
        'disbursed_at',
        'disbursement_method',
        'disbursement_details',
        'documents',
        'remarks',
        'settings'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'disbursed_at' => 'datetime',
        'documents' => 'array',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the organization that owns the loan.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the employee that owns the loan.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who approved the loan.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who rejected the loan.
     */
    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the user who disbursed the loan.
     */
    public function disburser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    /**
     * Get the repayments for the loan.
     */
    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    /**
     * Get the documents for the loan.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the transaction logs for the loan.
     */
    public function transactionLogs(): HasMany
    {
        return $this->hasMany(TransactionLog::class);
    }

    /**
     * Scope a query to only include active loans.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include pending loans.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved loans.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected loans.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope a query to only include disbursed loans.
     */
    public function scopeDisbursed($query)
    {
        return $query->where('status', 'disbursed');
    }

    /**
     * Scope a query to only include completed loans.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include defaulted loans.
     */
    public function scopeDefaulted($query)
    {
        return $query->where('status', 'defaulted');
    }

    /**
     * Get the loan's total interest amount.
     */
    public function getTotalInterestAttribute()
    {
        return $this->amount * ($this->interest_rate / 100) * ($this->term_months / 12);
    }

    /**
     * Get the loan's total amount (principal + interest).
     */
    public function getTotalAmountAttribute()
    {
        return $this->amount + $this->total_interest;
    }

    /**
     * Get the loan's monthly payment amount.
     */
    public function getMonthlyPaymentAttribute()
    {
        return $this->total_amount / $this->term_months;
    }

    /**
     * Get the loan's total repaid amount.
     */
    public function getTotalRepaidAttribute()
    {
        return $this->repayments()
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get the loan's remaining amount.
     */
    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->total_repaid;
    }

    /**
     * Get the loan's next payment due date.
     */
    public function getNextPaymentDueDateAttribute()
    {
        $lastPayment = $this->repayments()
            ->where('status', 'completed')
            ->latest()
            ->first();

        if (!$lastPayment) {
            return $this->start_date->addMonth();
        }

        return $lastPayment->payment_date->addMonth();
    }

    /**
     * Get the loan's days overdue.
     */
    public function getDaysOverdueAttribute()
    {
        if ($this->status !== 'active') {
            return 0;
        }

        $nextDueDate = $this->next_payment_due_date;
        if ($nextDueDate->isFuture()) {
            return 0;
        }

        return $nextDueDate->diffInDays(now());
    }

    /**
     * Check if the loan is overdue.
     */
    public function getIsOverdueAttribute()
    {
        return $this->days_overdue > 0;
    }

    /**
     * Get the loan's payment history.
     */
    public function getPaymentHistoryAttribute()
    {
        return $this->repayments()
            ->where('status', 'completed')
            ->orderBy('payment_date')
            ->get()
            ->map(function ($repayment) {
                return [
                    'date' => $repayment->payment_date,
                    'amount' => $repayment->amount,
                    'principal' => $repayment->principal_amount,
                    'interest' => $repayment->interest_amount,
                    'late_fee' => $repayment->late_fee_amount
                ];
            });
    }

    /**
     * Get the loan's payment schedule.
     */
    public function getPaymentScheduleAttribute()
    {
        $schedule = [];
        $remainingAmount = $this->total_amount;
        $paymentDate = $this->start_date;

        for ($i = 1; $i <= $this->term_months; $i++) {
            $paymentDate = $paymentDate->addMonth();
            $interestAmount = $remainingAmount * ($this->interest_rate / 100) / 12;
            $principalAmount = $this->monthly_payment - $interestAmount;
            $remainingAmount -= $principalAmount;

            $schedule[] = [
                'payment_number' => $i,
                'due_date' => $paymentDate,
                'payment_amount' => $this->monthly_payment,
                'principal_amount' => $principalAmount,
                'interest_amount' => $interestAmount,
                'remaining_amount' => max(0, $remainingAmount)
            ];
        }

        return $schedule;
    }
}
