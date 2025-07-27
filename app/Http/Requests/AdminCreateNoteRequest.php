<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminCreateNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['admin', 'superadmin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:10000000',
            'user_id' => 'required|exists:users,id',
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
            'title.required' => 'Note title is required',
            'title.max' => 'Note title cannot exceed 255 characters',
            'content.required' => 'Note content is required',
            'content.max' => 'Note content cannot exceed 10 million characters',
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'The specified user does not exist',
            'is_private.boolean' => 'Privacy setting must be true or false',
            'tags.array' => 'Tags must be provided as an array',
            'tags.max' => 'You cannot have more than 10 tags',
            'tags.*.string' => 'Each tag must be a string',
            'tags.*.max' => 'Each tag cannot exceed 50 characters',
        ];
    }
}
