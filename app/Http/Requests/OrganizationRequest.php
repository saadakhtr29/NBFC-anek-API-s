<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('organizations')->ignore($this->organization)
            ],
            'type' => 'required|string|max:100',
            'registration_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('organizations')->ignore($this->organization)
            ],
            'tax_number' => 'nullable|string|max:50',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'phone' => 'required|string|max:20',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('organizations')->ignore($this->organization)
            ],
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|max:2048', // 2MB max
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive,suspended',
            'founding_date' => 'required|date',
            'industry' => 'required|string|max:100',
            'size' => 'required|in:small,medium,large,enterprise',
            'annual_revenue' => 'nullable|numeric|min:0|max:999999999999.99',
            'currency' => 'required|string|size:3',
            'timezone' => 'required|string|max:50',
            'settings' => 'nullable|array',
            'settings.*' => 'string',
            'remarks' => 'nullable|string|max:1000'
        ];

        // Add password validation only for create
        if (!$this->organization) {
            $rules['password'] = 'required|string|min:8';
        } else {
            $rules['password'] = 'nullable|string|min:8';
        }

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
            'name.required' => 'The organization name is required.',
            'code.required' => 'The organization code is required.',
            'code.unique' => 'This organization code is already taken.',
            'type.required' => 'The organization type is required.',
            'registration_number.required' => 'The registration number is required.',
            'registration_number.unique' => 'This registration number is already registered.',
            'address.required' => 'The address is required.',
            'city.required' => 'The city is required.',
            'state.required' => 'The state is required.',
            'country.required' => 'The country is required.',
            'postal_code.required' => 'The postal code is required.',
            'phone.required' => 'The phone number is required.',
            'email.required' => 'The email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'The password is required.',
            'password.string' => 'The password must be a string.',
            'password.min' => 'The password must be at least 8 characters long.',
            'website.url' => 'Please provide a valid website URL.',
            'logo.image' => 'The logo must be an image file.',
            'logo.max' => 'The logo size cannot exceed 2MB.',
            'status.required' => 'The status is required.',
            'status.in' => 'The selected status is invalid.',
            'founding_date.required' => 'The founding date is required.',
            'founding_date.date' => 'Please provide a valid founding date.',
            'industry.required' => 'The industry is required.',
            'size.required' => 'The organization size is required.',
            'size.in' => 'The selected organization size is invalid.',
            'annual_revenue.numeric' => 'The annual revenue must be a number.',
            'annual_revenue.min' => 'The annual revenue must be at least 0.',
            'annual_revenue.max' => 'The annual revenue cannot exceed 999,999,999,999.99.',
            'currency.required' => 'The currency is required.',
            'currency.size' => 'The currency must be a 3-letter code.',
            'timezone.required' => 'The timezone is required.'
        ];
    }
} 