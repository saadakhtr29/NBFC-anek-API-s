<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Organization extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'type',
        'registration_number',
        'tax_number',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'email',
        'password',
        'website',
        'logo',
        'description',
        'status',
        'founding_date',
        'industry',
        'size',
        'annual_revenue',
        'currency',
        'timezone',
        'settings',
        'remarks'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'founding_date' => 'date',
        'annual_revenue' => 'decimal:2',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the users associated with the organization.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the employees of the organization.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get the loans of the organization.
     */
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Get the documents of the organization.
     */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the transaction logs of the organization.
     */
    public function transactionLogs()
    {
        return $this->hasMany(TransactionLog::class);
    }

    /**
     * Get the salaries of the organization.
     */
    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }

    /**
     * Get the attendances of the organization.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Scope a query to only include active organizations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include organizations of a given type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include organizations in a given industry.
     */
    public function scopeInIndustry($query, $industry)
    {
        return $query->where('industry', $industry);
    }

    /**
     * Get the organization's active employees count.
     */
    public function getActiveEmployeesCountAttribute()
    {
        return $this->employees()->where('status', 'active')->count();
    }

    /**
     * Get the organization's total active loans.
     */
    public function getActiveLoansCountAttribute()
    {
        return $this->loans()->where('status', 'active')->count();
    }

    /**
     * Get the organization's total loan amount.
     */
    public function getTotalLoanAmountAttribute()
    {
        return $this->loans()->where('status', 'active')->sum('amount');
    }

    /**
     * Get the organization's total repaid amount.
     */
    public function getTotalRepaidAmountAttribute()
    {
        return $this->loans()
            ->whereHas('repayments', function($query) {
                $query->where('status', 'completed');
            })
            ->sum('amount');
    }

    /**
     * Get the organization's remaining loan amount.
     */
    public function getRemainingLoanAmountAttribute()
    {
        return $this->total_loan_amount - $this->total_repaid_amount;
    }

    /**
     * Get the organization's document statistics.
     */
    public function getDocumentStatisticsAttribute()
    {
        return [
            'total_documents' => $this->documents()->count(),
            'total_size' => $this->documents()->sum('file_size'),
            'by_type' => $this->documents()
                ->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->get()
        ];
    }
}
