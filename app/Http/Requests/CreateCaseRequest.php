<?php

namespace App\Http\Requests;

use App\Services\FileUploadService;
use Illuminate\Foundation\Http\FormRequest;

class CreateCaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAdminAccess();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $fileUploadService = app(FileUploadService::class);
        $maxFileSize = $fileUploadService->getMaxFileSize() / 1024; // Convert to KB for Laravel validation
        $allowedTypes = implode(',', $fileUploadService->getAllowedTypes());

        return [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'report' => 'nullable|string',
            'course' => 'nullable|string|max:255',
            'topic' => 'nullable|string|max:255',
            'tag' => 'nullable|string|max:255',
            'principles' => 'nullable|string',
            'level' => 'nullable|string|max:255',
            'court' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'country' => 'nullable|string|max:255',
            'citation' => 'nullable|string|max:255',
            'judges' => 'nullable|string',
            'judicial_precedent' => 'nullable|string',
            
            // Similar cases rules
            'similar_case_ids' => 'sometimes|array|max:50',
            'similar_case_ids.*' => 'integer|exists:court_cases,id',
            
            // File upload rules
            'files' => 'sometimes|array|max:10',
            'files.*' => [
                'file',
                'max:' . $maxFileSize,
                'mimes:' . $allowedTypes
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $fileUploadService = app(FileUploadService::class);
        $maxSizeMB = $fileUploadService->getMaxFileSize() / 1024 / 1024;
        $allowedTypes = implode(', ', $fileUploadService->getAllowedTypes());

        return [
            'title.required' => 'Case title is required',
            'title.max' => 'Case title cannot exceed 255 characters',
            'body.required' => 'Case body is required',
            'court.max' => 'Court name cannot exceed 255 characters',
            'country.max' => 'Country name cannot exceed 255 characters',
            'citation.max' => 'Citation cannot exceed 255 characters',
            'date.date' => 'Please provide a valid date',
            
            // File upload messages
            'files.array' => 'Files must be provided as an array',
            'files.max' => 'You cannot upload more than 10 files at once',
            'files.*.file' => 'One or more uploaded files are not valid',
            'files.*.max' => "Each file size cannot exceed {$maxSizeMB}MB",
            'files.*.mimes' => "Only the following file types are allowed: {$allowedTypes}",
            
            // Similar cases messages
            'similar_case_ids.array' => 'Similar cases must be provided as an array',
            'similar_case_ids.max' => 'You cannot link more than 50 similar cases',
            'similar_case_ids.*.integer' => 'Each similar case ID must be a valid integer',
            'similar_case_ids.*.exists' => 'One or more similar case IDs do not exist',
        ];
    }
}