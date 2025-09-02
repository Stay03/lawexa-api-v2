<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => [
                'nullable', 
                'integer', 
                'exists:folders,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $parentFolder = \App\Models\Folder::find($value);
                        if ($parentFolder && !$parentFolder->isOwnedBy($this->user())) {
                            $fail('You can only create folders inside your own folders.');
                        }
                    }
                }
            ],
            'is_public' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Folder name is required.',
            'name.max' => 'Folder name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'parent_id.exists' => 'Parent folder does not exist.',
        ];
    }
}
