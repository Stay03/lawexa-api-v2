<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateNoteRequest extends FormRequest
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
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string|max:10000000',
            'user_id' => 'sometimes|exists:users,id',
            'status' => 'sometimes|in:draft,published',
            'is_private' => 'sometimes|boolean',
            'tags' => 'sometimes|array|max:10',
            'tags.*' => 'string|max:50',
            'price_ngn' => 'sometimes|nullable|numeric|min:0|max:99999999.99',
            'price_usd' => 'sometimes|nullable|numeric|min:0|max:99999999.99',
            // Video validation
            'videos' => 'sometimes|array',
            'videos.*.video_url' => 'required_with:videos|url|max:500',
            'videos.*.thumbnail_url' => 'nullable|url|max:500',
            'videos.*.platform' => 'nullable|in:youtube,dailymotion,other',
            'videos.*.sort_order' => 'nullable|integer|min:0',
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
            'user_id.exists' => 'The specified user does not exist',
            'status.in' => 'Status must be either draft or published',
            'is_private.boolean' => 'Privacy setting must be true or false',
            'tags.array' => 'Tags must be provided as an array',
            'tags.max' => 'You cannot have more than 10 tags',
            'tags.*.string' => 'Each tag must be a string',
            'tags.*.max' => 'Each tag cannot exceed 50 characters',
            'price_ngn.numeric' => 'Price in Naira must be a number',
            'price_ngn.min' => 'Price in Naira cannot be negative',
            'price_ngn.max' => 'Price in Naira is too high',
            'price_usd.numeric' => 'Price in USD must be a number',
            'price_usd.min' => 'Price in USD cannot be negative',
            'price_usd.max' => 'Price in USD is too high',
            'videos.array' => 'Videos must be provided as an array',
            'videos.*.video_url.required_with' => 'Video URL is required for each video',
            'videos.*.video_url.url' => 'Video URL must be a valid URL',
            'videos.*.video_url.max' => 'Video URL cannot exceed 500 characters',
            'videos.*.thumbnail_url.url' => 'Thumbnail URL must be a valid URL',
            'videos.*.thumbnail_url.max' => 'Thumbnail URL cannot exceed 500 characters',
            'videos.*.platform.in' => 'Platform must be youtube, dailymotion, or other',
            'videos.*.sort_order.integer' => 'Sort order must be an integer',
            'videos.*.sort_order.min' => 'Sort order cannot be negative',
        ];
    }
}
