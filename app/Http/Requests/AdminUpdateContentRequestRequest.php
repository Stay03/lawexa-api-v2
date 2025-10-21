<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateContentRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'status' => 'sometimes|in:pending,in_progress,fulfilled,rejected',

            // Polymorphic content linkage
            'created_content_type' => [
                'sometimes',
                'string',
                Rule::in([
                    'App\Models\CourtCase',
                    'App\Models\Statute',
                    'App\Models\StatuteProvision',
                    'App\Models\StatuteDivision',
                ]),
            ],
            'created_content_id' => 'required_with:created_content_type|integer',

            // Rejection
            'rejection_reason' => 'nullable|string|max:2000',

            // Admin notes (via Commentable trait)
            'admin_notes' => 'nullable|string|max:2000',
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
            'status.in' => 'Invalid status. Must be: pending, in_progress, fulfilled, or rejected.',
            'created_content_type.in' => 'Invalid content type.',
            'created_content_id.required_with' => 'Content ID is required when specifying content type.',
            'rejection_reason.max' => 'Rejection reason must not exceed 2000 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $request = $this;
            $contentRequest = $this->route('contentRequest');

            // If marking as fulfilled, ensure created_content is provided
            if ($request->status === 'fulfilled' &&
                (!$request->created_content_type || !$request->created_content_id)) {
                $validator->errors()->add(
                    'created_content_id',
                    'You must link created content when marking request as fulfilled.'
                );
            }

            // If providing created_content, verify it exists
            if ($request->created_content_type && $request->created_content_id) {
                $model = $request->created_content_type;

                if (!class_exists($model)) {
                    $validator->errors()->add('created_content_type', 'Invalid model class.');
                    return;
                }

                if (!$model::find($request->created_content_id)) {
                    $validator->errors()->add('created_content_id', 'The specified content does not exist.');
                }
            }
        });
    }
}
