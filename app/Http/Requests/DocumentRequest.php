<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'description' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50'
        ];

        // Add file validation for new documents
        if ($this->isMethod('post')) {
            $rules['file'] = 'required|file|max:10240'; // 10MB max
            $rules['organization_id'] = 'required|exists:organizations,id';
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
            'title.required' => 'The document title is required.',
            'title.max' => 'The document title cannot exceed 255 characters.',
            'type.required' => 'The document type is required.',
            'type.max' => 'The document type cannot exceed 50 characters.',
            'description.max' => 'The description cannot exceed 1000 characters.',
            'tags.*.max' => 'Each tag cannot exceed 50 characters.',
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is invalid.',
            'file.max' => 'The file size cannot exceed 10MB.',
            'organization_id.required' => 'The organization is required.',
            'organization_id.exists' => 'The selected organization does not exist.'
        ];
    }
} 