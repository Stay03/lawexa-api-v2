<?php

namespace App\Http\Requests;

use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;

class CreateSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'exists:plans,id',
                function ($attribute, $value, $fail) {
                    $plan = Plan::find($value);
                    if ($plan && !$plan->is_active) {
                        $fail('The selected plan is not available for subscription.');
                    }
                },
            ],
            'authorization_code' => 'nullable|string',
            'callback_url' => 'nullable|url',
            'metadata' => 'nullable|array',
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
            'plan_id.required' => 'Please select a subscription plan',
            'plan_id.exists' => 'The selected plan does not exist',
            'callback_url.url' => 'Callback URL must be a valid URL',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->user()->hasActiveSubscription()) {
                $validator->errors()->add('subscription', 'You already have an active subscription. Please cancel it first or use the switch plan feature.');
            }
        });
    }
}
