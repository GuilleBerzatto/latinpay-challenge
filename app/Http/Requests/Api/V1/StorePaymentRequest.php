<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest {
    
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'merchant_id' => 'required|integer',
            'customer_document' => 'required|string|max:20',
            'description' => 'nullable|string|max:255',
        ];
    }
}