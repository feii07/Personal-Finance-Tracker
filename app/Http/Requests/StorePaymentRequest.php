<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
            'amount' => 'required|numeric|min:10000|max:1000000', // IDR 10k - 1M
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Payment amount is required',
            'amount.numeric' => 'Payment amount must be a number',
            'amount.min' => 'Minimum payment amount is IDR 10,000',
            'amount.max' => 'Maximum payment amount is IDR 1,000,000',
        ];
    }
}
