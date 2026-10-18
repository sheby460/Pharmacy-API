<?php

namespace App\Http\Requests\Sale;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmSaleReturnRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authentication is required. Apply your application's
        // refund-specific permission or policy here as well.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
