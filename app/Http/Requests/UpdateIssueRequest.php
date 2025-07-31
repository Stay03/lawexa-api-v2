<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $issue = $this->route('issue');
        return $issue && $this->user()->id === $issue->user_id;
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
            'area' => 'sometimes|nullable|in:frontend,backend,both',
            'category' => 'sometimes|nullable|string|max:100',
            'browser_info' => 'sometimes|nullable|array',
            'environment_info' => 'sometimes|nullable|array',
            'steps_to_reproduce' => 'sometimes|nullable|string',
            'expected_behavior' => 'sometimes|nullable|string',
            'actual_behavior' => 'sometimes|nullable|string',
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
            'area.in' => 'Invalid area selected.',
            'file_ids.*.exists' => 'One or more selected files do not exist.',
        ];
    }
}
