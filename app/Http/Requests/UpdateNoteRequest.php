<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $note = $this->route('note');
        return $this->user() && $note->isOwnedBy($this->user());
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
            'content' => 'sometimes|string|max:10000000',
            'is_private' => 'sometimes|boolean',
            'tags' => 'sometimes|array|max:10',
            'tags.*' => 'string|max:50',
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
            'title.max' => 'Note title cannot exceed 255 characters',
            'content.max' => 'Note content cannot exceed 10 million characters',
            'is_private.boolean' => 'Privacy setting must be true or false',
            'tags.array' => 'Tags must be provided as an array',
            'tags.max' => 'You cannot have more than 10 tags',
            'tags.*.string' => 'Each tag must be a string',
            'tags.*.max' => 'Each tag cannot exceed 50 characters',
        ];
    }
}
