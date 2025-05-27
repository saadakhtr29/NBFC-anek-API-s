<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'user_id',
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'date_of_birth',
        'date_of_joining',
        'designation',
        'department',
        'salary',
        'status',
        'employment_type',
        'bank_name',
        'bank_account_number',
        'bank_ifsc_code',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'documents',
        'remarks'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_joining' => 'date',
        'salary' => 'decimal:2',
        'documents' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the organization that owns the employee.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user associated with the employee.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the loans for the employee.
     */
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Get the repayments for the employee.
     */
    public function repayments()
    {
        return $this->hasMany(LoanRepayment::class);
    }

    /**
     * Get the transaction logs for the employee.
     */
    public function transactionLogs()
    {
        return $this->hasMany(TransactionLog::class);
    }

    /**
     * Scope a query to only include active employees.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include employees of a given organization.
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope a query to only include employees with a given designation.
     */
    public function scopeWithDesignation($query, $designation)
    {
        return $query->where('designation', $designation);
    }

    /**
     * Scope a query to only include employees in a given department.
     */
    public function scopeInDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Get the employee's full name.
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the employee's active loans.
     */
    public function getActiveLoansAttribute()
    {
        return $this->loans()->where('status', 'active')->get();
    }

    /**
     * Get the employee's total loan amount.
     */
    public function getTotalLoanAmountAttribute()
    {
        return $this->loans()->where('status', 'active')->sum('amount');
    }

    /**
     * Get the employee's total repaid amount.
     */
    public function getTotalRepaidAmountAttribute()
    {
        return $this->repayments()->sum('amount');
    }

    /**
     * Get the employee's remaining loan amount.
     */
    public function getRemainingLoanAmountAttribute()
    {
        return $this->total_loan_amount - $this->total_repaid_amount;
    }
}
