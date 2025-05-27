<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
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
            'user_id' => 'nullable|exists:users,id',
            'employee_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('employees')->ignore($this->employee)
            ],
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('employees')->ignore($this->employee)
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'date_of_joining' => 'required|date',
            'designation' => 'required|string|max:100',
            'department' => 'required|string|max:100',
            'salary' => 'required|numeric|min:0|max:999999999.99',
            'status' => 'required|in:active,inactive,on_leave,terminated',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_ifsc_code' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string|max:50',
            'documents' => 'nullable|array',
            'documents.*' => 'string',
            'remarks' => 'nullable|string|max:1000'
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
            'organization_id.exists' => 'The selected organization does not exist.',
            'user_id.exists' => 'The selected user does not exist.',
            'employee_id.required' => 'The employee ID is required.',
            'employee_id.unique' => 'This employee ID is already taken.',
            'first_name.required' => 'The first name is required.',
            'last_name.required' => 'The last name is required.',
            'email.required' => 'The email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'date_of_birth.before' => 'The date of birth must be a date before today.',
            'date_of_joining.required' => 'The date of joining is required.',
            'designation.required' => 'The designation is required.',
            'department.required' => 'The department is required.',
            'salary.required' => 'The salary is required.',
            'salary.numeric' => 'The salary must be a number.',
            'salary.min' => 'The salary must be at least 0.',
            'salary.max' => 'The salary cannot exceed 999,999,999.99.',
            'status.required' => 'The status is required.',
            'status.in' => 'The selected status is invalid.',
            'employment_type.required' => 'The employment type is required.',
            'employment_type.in' => 'The selected employment type is invalid.'
        ];
    }
} 