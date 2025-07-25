<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DirectUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by auth:sanctum middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'original_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[^<>:"|?*]+$/', // Prevent invalid filename characters
            ],
            'mime_type' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9][a-zA-Z0-9!#$&\-\^_]*\/[a-zA-Z0-9][a-zA-Z0-9!#$&\-\^_.]*$/',
            ],
            'size' => [
                'required',
                'integer',
                'min:1',
                'max:' . (5 * 1024 * 1024 * 1024), // 5GB max
            ],
            'category' => [
                'required',
                'string',
                'in:general,legal,case,document,image',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'original_name.required' => 'File name is required',
            'original_name.regex' => 'File name contains invalid characters',
            'mime_type.required' => 'File MIME type is required',
            'mime_type.regex' => 'Invalid MIME type format',
            'size.required' => 'File size is required',
            'size.min' => 'File size must be at least 1 byte',
            'size.max' => 'File size cannot exceed 5GB',
            'category.required' => 'File category is required',
            'category.in' => 'Invalid file category. Allowed: general, legal, case, document, image',
        ];
    }

    /**
     * Get validated data with defaults applied.
     */
    public function getValidatedDataWithDefaults(): array
    {
        $validated = $this->validated();
        
        // Set default category if not provided
        $validated['category'] = $validated['category'] ?? 'general';
        
        return $validated;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize the file extension
        if ($this->has('original_name')) {
            $originalName = $this->input('original_name');
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            
            // If no extension, try to infer from MIME type
            if (empty($extension) && $this->has('mime_type')) {
                $mimeType = $this->input('mime_type');
                $extension = $this->getExtensionFromMimeType($mimeType);
                
                if ($extension) {
                    $this->merge([
                        'original_name' => $originalName . '.' . $extension
                    ]);
                }
            }
        }
    }

    /**
     * Get file extension from MIME type.
     */
    private function getExtensionFromMimeType(string $mimeType): ?string
    {
        $mimeToExtension = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'text/plain' => 'txt',
            'application/rtf' => 'rtf',
            'text/rtf' => 'rtf',
        ];

        return $mimeToExtension[$mimeType] ?? null;
    }
}