<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasAdminAccess();
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
            'description' => 'sometimes|string',
            'type' => 'sometimes|in:bug,feature_request,improvement,other',
            'severity' => 'sometimes|in:low,medium,high,critical',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'status' => 'sometimes|in:open,in_progress,resolved,closed,duplicate',
            'area' => 'sometimes|nullable|in:frontend,backend,both',
            'category' => 'sometimes|nullable|string|max:100',
            'browser_info' => 'sometimes|nullable|array',
            'environment_info' => 'sometimes|nullable|array',
            'steps_to_reproduce' => 'sometimes|nullable|string',
            'expected_behavior' => 'sometimes|nullable|string',
            'actual_behavior' => 'sometimes|nullable|string',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
            'ai_analysis' => 'sometimes|nullable|string',
            'admin_notes' => 'sometimes|nullable|string',
            'file_ids' => 'sometimes|nullable|array',
            'file_ids.*' => 'exists:files,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Invalid issue type selected.',
            'severity.in' => 'Invalid severity level selected.',
            'priority.in' => 'Invalid priority level selected.',
            'status.in' => 'Invalid status selected.',
            'area.in' => 'Invalid area selected.',
            'assigned_to.exists' => 'The selected user does not exist.',
            'file_ids.*.exists' => 'One or more selected files do not exist.',
        ];
    }
}
