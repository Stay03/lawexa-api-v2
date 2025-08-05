<?php

namespace App\Http\Requests;

use App\Services\FileUploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;

class UpdateStatuteRequest extends FormRequest
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
            'title' => 'sometimes|required|string|max:255',
            'short_title' => 'nullable|string|max:255',
            'year_enacted' => 'nullable|integer|min:1800|max:' . (date('Y') + 10),
            'commencement_date' => 'nullable|date',
            'status' => ['sometimes', 'required', Rule::in(['active', 'repealed', 'amended', 'suspended'])],
            'repealed_date' => 'nullable|date|required_if:status,repealed',
            'repealing_statute_id' => 'nullable|exists:statutes,id|required_if:status,repealed',
            'parent_statute_id' => 'nullable|exists:statutes,id',
            'jurisdiction' => 'sometimes|required|string|max:100',
            'country' => 'sometimes|required|string|max:100',
            'state' => 'nullable|string|max:100',
            'local_government' => 'nullable|string|max:100',
            'citation_format' => 'nullable|string|max:255',
            'sector' => 'nullable|string|max:100',
            'tags' => 'nullable|array|max:20',
            'tags.*' => 'string|max:50',
            'description' => 'sometimes|required|string',
            'range' => 'nullable|string|max:255',
            'files' => 'nullable|array|max:10',
            'files.*' => [
                'file',
                'max:' . $maxFileSize,
                'mimes:' . $allowedTypes
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Get the statute being updated from the route
            $statuteId = $this->route('id');
            
            // Custom validation logic
            if ($this->parent_statute_id && $this->parent_statute_id == $statuteId) {
                $validator->errors()->add('parent_statute_id', 'A statute cannot be its own parent.');
            }
            
            if ($this->repealing_statute_id && $this->repealing_statute_id == $statuteId) {
                $validator->errors()->add('repealing_statute_id', 'A statute cannot repeal itself.');
            }
            
            // Validate commencement_date is not before year_enacted
            if ($this->year_enacted && $this->commencement_date) {
                $commencementYear = date('Y', strtotime($this->commencement_date));
                if ($commencementYear < $this->year_enacted) {
                    $validator->errors()->add('commencement_date', 'Commencement date cannot be before the year enacted.');
                }
            }
        });
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
            'title.required' => 'The statute title is required.',
            'title.max' => 'The statute title cannot exceed 255 characters.',
            'status.required' => 'The statute status is required.',
            'jurisdiction.required' => 'The jurisdiction is required.',
            'country.required' => 'The country is required.',
            'description.required' => 'The statute description is required.',
            'repealed_date.required_if' => 'The repealed date is required when status is repealed.',
            'repealing_statute_id.required_if' => 'The repealing statute is required when status is repealed.',
            'year_enacted.min' => 'The year enacted must be at least 1800.',
            'year_enacted.max' => 'The year enacted cannot be more than 10 years in the future.',
            
            // File upload messages
            'files.array' => 'Files must be provided as an array.',
            'files.max' => 'You cannot upload more than 10 files at once.',
            'files.*.file' => 'One or more uploaded files are not valid.',
            'files.*.max' => "Each file size cannot exceed {$maxSizeMB}MB.",
            'files.*.mimes' => "Only the following file types are allowed: {$allowedTypes}.",
            
            // Tags messages
            'tags.array' => 'Tags must be provided as an array.',
            'tags.max' => 'You cannot add more than 20 tags.',
            'tags.*.string' => 'Each tag must be a string.',
            'tags.*.max' => 'Each tag cannot exceed 50 characters.',
        ];
    }
}