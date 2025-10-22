<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveFeedbackToIssuesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User must be an admin (handled by middleware)
        return $this->user() && $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // No validation rules needed for this request
        // The controller will validate that the feedback hasn't already been moved
        return [];
    }
}
