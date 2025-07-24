<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCaseRequest extends FormRequest
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
        return [
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
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
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.max' => 'Case title cannot exceed 255 characters',
            'court.max' => 'Court name cannot exceed 255 characters',
            'country.max' => 'Country name cannot exceed 255 characters',
            'citation.max' => 'Citation cannot exceed 255 characters',
            'date.date' => 'Please provide a valid date',
        ];
    }
}