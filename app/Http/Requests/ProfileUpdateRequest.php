<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'profession' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'area_of_expertise' => 'required|string|max:150',
            'university' => 'nullable|required_if:profession,student|string|max:200',
            'level' => 'nullable|required_if:profession,student|string|max:50',
            'work_experience' => 'nullable|integer|min:0|max:50',
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
            'profession.required' => 'Profession is required',
            'country.required' => 'Country is required',
            'area_of_expertise.required' => 'Area of expertise is required',
            'university.required_if' => 'University is required for students',
            'level.required_if' => 'Academic level is required for students',
            'work_experience.integer' => 'Work experience must be a number',
            'work_experience.min' => 'Work experience cannot be negative',
            'work_experience.max' => 'Work experience cannot exceed 50 years',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'area_of_expertise' => 'area of expertise',
            'work_experience' => 'work experience',
        ];
    }
}
