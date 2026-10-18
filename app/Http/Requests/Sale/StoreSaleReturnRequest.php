<?php

namespace App\Http\Requests\Sale;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Enforce the appropriate return permission in route middleware
        // or replace this with your project's authorization policy.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'sale_id' => [
                'required',
                'integer',
                'min:1',
                'exists:sales,id',
            ],

            'reason' => [
                'required',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'refund_method' => [
                'nullable',
                'string',
                Rule::in(['CASH', 'MOBILE_MONEY', 'CARD']),
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.sale_item_id' => [
                'required',
                'integer',
                'distinct',
                'min:1',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'items.*.disposition' => [
                'required',
                Rule::in(['RESTOCK', 'QUARANTINE']),
            ],

            'items.*.notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
