<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookmarkRequest extends FormRequest
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
        return [
            'bookmarkable_type' => [
                'required',
                'string',
                'in:App\\Models\\CourtCase,App\\Models\\Note,App\\Models\\Statute,App\\Models\\StatuteDivision,App\\Models\\StatuteProvision,App\\Models\\Folder'
            ],
            'bookmarkable_id' => 'required|integer|min:1'
        ];
    }

    public function messages(): array
    {
        return [
            'bookmarkable_type.in' => 'The selected item type is not supported for bookmarking.',
            'bookmarkable_id.required' => 'The item ID is required.',
            'bookmarkable_id.integer' => 'The item ID must be a valid number.',
            'bookmarkable_id.min' => 'The item ID must be greater than 0.',
        ];
    }
}
