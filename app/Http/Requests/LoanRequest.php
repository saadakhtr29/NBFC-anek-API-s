<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'organization_id' => 'required|exists:organizations,id',
            'employee_id' => 'required|exists:employees,id',
            'loan_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('loans')->ignore($this->loan)
            ],
            'type' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0|max:999999999999.99',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'term_months' => 'required|integer|min:1|max:360',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'status' => [
                'required',
                Rule::in(['pending', 'approved', 'rejected', 'disbursed', 'active', 'completed', 'defaulted'])
            ],
            'purpose' => 'required|string|max:1000',
            'collateral' => 'nullable|string|max:1000',
            'guarantor_name' => 'nullable|string|max:255',
            'guarantor_contact' => 'nullable|string|max:50',
            'guarantor_relationship' => 'nullable|string|max:100',
            'approved_by' => 'nullable|exists:users,id',
            'approved_at' => 'nullable|date',
            'rejected_by' => 'nullable|exists:users,id',
            'rejected_at' => 'nullable|date',
            'rejection_reason' => 'nullable|string|max:1000',
            'disbursed_by' => 'nullable|exists:users,id',
            'disbursed_at' => 'nullable|date',
            'disbursement_method' => 'nullable|string|max:100',
            'disbursement_details' => 'nullable|array',
            'documents' => 'nullable|array',
            'documents.*' => 'string',
            'remarks' => 'nullable|string|max:1000',
            'settings' => 'nullable|array',
            'settings.*' => 'string'
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'organization_id.required' => 'The organization is required.',
            'organization_id.exists' => 'The selected organization is invalid.',
            'employee_id.required' => 'The employee is required.',
            'employee_id.exists' => 'The selected employee is invalid.',
            'loan_number.required' => 'The loan number is required.',
            'loan_number.unique' => 'This loan number is already taken.',
            'type.required' => 'The loan type is required.',
            'amount.required' => 'The loan amount is required.',
            'amount.numeric' => 'The loan amount must be a number.',
            'amount.min' => 'The loan amount must be at least 0.',
            'amount.max' => 'The loan amount cannot exceed 999,999,999,999.99.',
            'interest_rate.required' => 'The interest rate is required.',
            'interest_rate.numeric' => 'The interest rate must be a number.',
            'interest_rate.min' => 'The interest rate must be at least 0.',
            'interest_rate.max' => 'The interest rate cannot exceed 100.',
            'term_months.required' => 'The loan term is required.',
            'term_months.integer' => 'The loan term must be a whole number.',
            'term_months.min' => 'The loan term must be at least 1 month.',
            'term_months.max' => 'The loan term cannot exceed 360 months.',
            'start_date.required' => 'The start date is required.',
            'start_date.date' => 'Please provide a valid start date.',
            'start_date.after_or_equal' => 'The start date must be today or a future date.',
            'end_date.required' => 'The end date is required.',
            'end_date.date' => 'Please provide a valid end date.',
            'end_date.after' => 'The end date must be after the start date.',
            'status.required' => 'The status is required.',
            'status.in' => 'The selected status is invalid.',
            'purpose.required' => 'The loan purpose is required.',
            'approved_by.exists' => 'The selected approver is invalid.',
            'approved_at.date' => 'Please provide a valid approval date.',
            'rejected_by.exists' => 'The selected rejector is invalid.',
            'rejected_at.date' => 'Please provide a valid rejection date.',
            'disbursed_by.exists' => 'The selected disburser is invalid.',
            'disbursed_at.date' => 'Please provide a valid disbursement date.',
            'disbursement_method.string' => 'The disbursement method must be a string.',
            'disbursement_details.array' => 'The disbursement details must be an array.',
            'documents.array' => 'The documents must be an array.',
            'documents.*.string' => 'Each document must be a string.',
            'settings.array' => 'The settings must be an array.',
            'settings.*.string' => 'Each setting must be a string.'
        ];
    }
} 