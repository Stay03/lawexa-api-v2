<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateContentRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|in:case,statute,provision,division',
            'title' => 'required|string|max:500',
            'additional_notes' => 'nullable|string|max:2000',

            // For provisions/divisions
            'statute_id' => 'required_if:type,provision,division|nullable|exists:statutes,id',
            'parent_division_id' => 'nullable|exists:statute_divisions,id',
            'parent_provision_id' => 'nullable|exists:statute_provisions,id',
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
            'type.required' => 'Please specify the type of content you are requesting.',
            'type.in' => 'Invalid content type. Must be: case, statute, provision, or division.',
            'title.required' => 'Please provide a title for the requested content.',
            'title.max' => 'Title must not exceed 500 characters.',
            'additional_notes.max' => 'Additional notes must not exceed 2000 characters.',
            'statute_id.required_if' => 'Statute ID is required for provision and division requests.',
            'statute_id.exists' => 'The selected statute does not exist.',
            'parent_division_id.exists' => 'The selected parent division does not exist.',
            'parent_provision_id.exists' => 'The selected parent provision does not exist.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Default to 'case' type if not specified
        if (!$this->has('type')) {
            $this->merge(['type' => 'case']);
        }

        // Sanitize title and notes
        if ($this->has('title')) {
            $this->merge(['title' => strip_tags($this->title)]);
        }

        if ($this->has('additional_notes')) {
            $this->merge(['additional_notes' => strip_tags($this->additional_notes)]);
        }
    }
}
