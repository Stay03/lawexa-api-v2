<?php

namespace App\Http\Requests;

use App\Services\FileUploadService;
use Illuminate\Foundation\Http\FormRequest;

class CreateIssueRequest extends FormRequest
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
        $fileUploadService = app(FileUploadService::class);
        $maxFileSize = $fileUploadService->getMaxFileSize() / 1024; // Convert to KB for Laravel validation
        $allowedTypes = implode(',', $fileUploadService->getAllowedTypes());

        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'nullable|in:bug,feature_request,improvement,other',
            'severity' => 'nullable|in:low,medium,high,critical',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'area' => 'nullable|in:frontend,backend,both',
            'category' => 'nullable|string|max:100',
            'browser_info' => 'nullable|array',
            'environment_info' => 'nullable|array',
            'steps_to_reproduce' => 'nullable|string',
            'expected_behavior' => 'nullable|string',
            'actual_behavior' => 'nullable|string',
            'file_ids' => 'nullable|array',
            'file_ids.*' => 'exists:files,id',
            
            // File upload rules
            'files' => 'sometimes|array|max:10',
            'files.*' => [
                'file',
                'max:' . $maxFileSize,
                'mimes:' . $allowedTypes
            ],
            'file_category' => 'nullable|string|in:general,legal,case,document,image,case_reports,issue',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $fileUploadService = app(FileUploadService::class);
        $maxSizeMB = $fileUploadService->getMaxFileSize() / 1024 / 1024;
        $allowedTypes = implode(', ', $fileUploadService->getAllowedTypes());

        return [
            'title.required' => 'Issue title is required.',
            'description.required' => 'Issue description is required.',
            'type.in' => 'Invalid issue type selected.',
            'severity.in' => 'Invalid severity level selected.',
            'priority.in' => 'Invalid priority level selected.',
            'area.in' => 'Invalid area selected.',
            'file_ids.*.exists' => 'One or more selected files do not exist.',
            
            // File upload messages
            'files.array' => 'Files must be provided as an array.',
            'files.max' => 'You cannot upload more than 10 files at once.',
            'files.*.file' => 'One or more uploaded files are not valid.',
            'files.*.max' => "Each file size cannot exceed {$maxSizeMB}MB.",
            'files.*.mimes' => "Only the following file types are allowed: {$allowedTypes}.",
            'file_category.in' => 'Invalid file category selected.',
        ];
    }
}
