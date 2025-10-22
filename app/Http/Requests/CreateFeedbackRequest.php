<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateFeedbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User must be authenticated (handled by auth:sanctum middleware)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'feedback_text' => ['required', 'string', 'min:10', 'max:5000'],
            'content_type' => [
                'nullable',
                'string',
                Rule::in([
                    'App\Models\CourtCase',
                    'App\Models\Statute',
                    'App\Models\StatuteProvision',
                    'App\Models\StatuteDivision',
                    'App\Models\Note',
                ]),
            ],
            'content_id' => [
                'nullable',
                'integer',
                'required_with:content_type',
            ],
            'page' => ['nullable', 'string', 'max:100'],
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'], // 5MB max per image
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'feedback_text.required' => 'Feedback text is required.',
            'feedback_text.min' => 'Feedback must be at least 10 characters.',
            'feedback_text.max' => 'Feedback cannot exceed 5000 characters.',
            'content_id.required_with' => 'Content ID is required when content type is specified.',
            'images.max' => 'You can upload a maximum of 4 images.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Images must be in JPEG, PNG, GIF, or WebP format.',
            'images.*.max' => 'Each image must not exceed 5MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // If content_type and content_id are not provided, set them to null
        if (!$this->has('content_type')) {
            $this->merge(['content_type' => null]);
        }
        if (!$this->has('content_id')) {
            $this->merge(['content_id' => null]);
        }
    }
}
