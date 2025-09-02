<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FolderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_type' => [
                'required', 
                'string', 
                Rule::in(['case', 'note', 'statute', 'statute_provision', 'statute_division'])
            ],
            'item_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_type.required' => 'Item type is required.',
            'item_type.in' => 'Invalid item type. Must be one of: case, note, statute, statute_provision, statute_division.',
            'item_id.required' => 'Item ID is required.',
            'item_id.integer' => 'Item ID must be a valid integer.',
            'item_id.min' => 'Item ID must be greater than 0.',
        ];
    }
}
