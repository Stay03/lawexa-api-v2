<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePlanRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|integer|min:100',
            'interval' => 'required|in:hourly,daily,weekly,monthly,quarterly,biannually,annually',
            'invoice_limit' => 'nullable|integer|min:0',
            'send_invoices' => 'boolean',
            'send_sms' => 'boolean',
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
            'name.required' => 'Plan name is required',
            'amount.required' => 'Plan amount is required',
            'amount.min' => 'Plan amount must be at least ₦1.00 (100 kobo)',
            'interval.required' => 'Billing interval is required',
            'interval.in' => 'Invalid billing interval',
        ];
    }
}
