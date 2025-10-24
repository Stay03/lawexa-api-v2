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
        // User must have admin access (admin, researcher, or superadmin)
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
            'type' => ['nullable', 'string', 'in:bug,feature_request,improvement,other'],
            'severity' => ['nullable', 'string', 'in:low,medium,high,critical'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'status' => ['nullable', 'string', 'in:open,in_progress,resolved,closed,duplicate'],
            'area' => ['nullable', 'string', 'in:frontend,backend,both,ai-ml-research'],
            'category' => ['nullable', 'string', 'max:100'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'admin_notes' => ['nullable', 'string'],
        ];
    }
}
