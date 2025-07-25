<?php

namespace App\Http\Requests;

use App\Services\FileUploadService;
use Illuminate\Foundation\Http\FormRequest;

class FileUploadRequest extends FormRequest
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

        $rules = [
            'category' => 'sometimes|string|max:100',
            'disk' => 'sometimes|string|in:local,public,s3',
            'replace_existing' => 'sometimes|boolean',
        ];

        // Handle single file upload
        if ($this->has('file')) {
            $rules['file'] = [
                'required',
                'file',
                'max:' . $maxFileSize,
                'mimes:' . $allowedTypes
            ];
        }

        // Handle multiple file upload
        if ($this->has('files')) {
            $rules['files'] = 'required|array|max:10';
            $rules['files.*'] = [
                'file',
                'max:' . $maxFileSize,
                'mimes:' . $allowedTypes
            ];
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        $fileUploadService = app(FileUploadService::class);
        $maxSizeMB = $fileUploadService->getMaxFileSize() / 1024 / 1024;
        $allowedTypes = implode(', ', $fileUploadService->getAllowedTypes());

        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is not valid.',
            'file.max' => "The file size cannot exceed {$maxSizeMB}MB.",
            'file.mimes' => "Only the following file types are allowed: {$allowedTypes}.",
            
            'files.required' => 'Please select at least one file to upload.',
            'files.array' => 'Files must be provided as an array.',
            'files.max' => 'You cannot upload more than 10 files at once.',
            'files.*.file' => 'One or more uploaded files are not valid.',
            'files.*.max' => "Each file size cannot exceed {$maxSizeMB}MB.",
            'files.*.mimes' => "Only the following file types are allowed: {$allowedTypes}.",
            
            'category.string' => 'Category must be a valid string.',
            'category.max' => 'Category cannot exceed 100 characters.',
            'disk.string' => 'Storage disk must be a valid string.',
            'disk.in' => 'Storage disk must be one of: local, public, s3.',
            'replace_existing.boolean' => 'Replace existing must be true or false.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'file' => 'file',
            'files' => 'files',
            'files.*' => 'file',
            'category' => 'category',
            'disk' => 'storage disk',
            'replace_existing' => 'replace existing files',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Ensure either 'file' or 'files' is provided, but not both
            if ($this->has('file') && $this->has('files')) {
                $validator->errors()->add('file', 'Please provide either a single file or multiple files, not both.');
            }

            // Ensure at least one file input is provided
            if (!$this->has('file') && !$this->has('files')) {
                $validator->errors()->add('file', 'Please provide a file or files to upload.');
            }
        });
    }

    /**
     * Get the validated data with defaults.
     */
    public function getValidatedDataWithDefaults(): array
    {
        $validated = $this->validated();
        
        return array_merge([
            'category' => 'general',
            'disk' => config('filesystems.default', 'local'),
            'replace_existing' => false,
        ], $validated);
    }

    /**
     * Check if this is a single file upload request.
     */
    public function isSingleFileUpload(): bool
    {
        return $this->has('file') && !$this->has('files');
    }

    /**
     * Check if this is a multiple file upload request.
     */
    public function isMultipleFileUpload(): bool
    {
        return $this->has('files') && !$this->has('file');
    }

    /**
     * Get the uploaded file(s) as an array.
     */
    public function getUploadedFiles(): array
    {
        if ($this->isSingleFileUpload()) {
            return [$this->file('file')];
        }

        if ($this->isMultipleFileUpload()) {
            return $this->file('files');
        }

        return [];
    }
}