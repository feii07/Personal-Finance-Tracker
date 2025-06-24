<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DonationPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:5000|max:10000000', // IDR 5k - 10M
            'donor_name' => 'required_if:is_anonymous,false|string|max:100',
            'message' => 'nullable|string|max:500',
            'is_anonymous' => 'boolean',
            'show_in_list' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Donation amount is required',
            'amount.numeric' => 'Donation amount must be a number',
            'amount.min' => 'Minimum donation amount is IDR 5,000',
            'amount.max' => 'Maximum donation amount is IDR 10,000,000',
            'donor_name.required_if' => 'Donor name is required for non-anonymous donations',
            'donor_name.max' => 'Donor name cannot exceed 100 characters',
            'message.max' => 'Message cannot exceed 500 characters',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_anonymous' => $this->boolean('is_anonymous'),
            'show_in_list' => $this->boolean('show_in_list', true),
        ]);
    }
}
