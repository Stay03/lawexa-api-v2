<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1', 'max:2000'],
            'commentable_type' => ['required', 'string', 'in:Issue,Note'],
            'commentable_id' => ['required', 'integer', 'min:1'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Comment content is required.',
            'content.min' => 'Comment must be at least 1 character long.',
            'content.max' => 'Comment cannot exceed 2000 characters.',
            'commentable_type.required' => 'Commentable type is required.',
            'commentable_type.in' => 'Invalid commentable type. Must be Issue or Note.',
            'commentable_id.required' => 'Commentable ID is required.',
            'commentable_id.integer' => 'Commentable ID must be a valid integer.',
            'parent_id.exists' => 'Parent comment does not exist.',
        ];
    }
}
