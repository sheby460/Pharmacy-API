<?php

namespace App\Http\Requests\Sale;

use App\Enums\PaymentMethod;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized
     * to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply
     * to the request.
     */
    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Sale Information
            |--------------------------------------------------------------------------
            */

            'customer_id' => [
                'nullable',
                'integer',
                'exists:customers,id',
            ],

            'discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'tax' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Sale Items
            |--------------------------------------------------------------------------
            */

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.drug_batch_id' => [
                'required',
                'integer',
                'exists:drug_batches,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            /*
            |--------------------------------------------------------------------------
            | Sale Payments
            |--------------------------------------------------------------------------
            */

            'payments' => [
                'required',
                'array',
                'min:1',
            ],

            'payments.*.payment_method' => [
                'required',
                Rule::enum(PaymentMethod::class),
            ],

            'payments.*.amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'payments.*.reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'payments.*.notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Validate duplicate batch entries.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $batchIds = collect(
                $this->input('items', [])
            )
                ->pluck('drug_batch_id')
                ->filter();

            if ($batchIds->duplicates()->isNotEmpty()) {
                $validator->errors()->add(
                    'items',
                    'Each drug batch can only appear once in a sale.'
                );
            }
        });
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'customer_id.exists' =>
            'The selected customer does not exist.',

            'items.required' =>
            'At least one sale item is required.',

            'items.min' =>
            'A sale must contain at least one item.',

            'items.*.drug_batch_id.required' =>
            'A drug batch is required for each sale item.',

            'items.*.drug_batch_id.exists' =>
            'The selected drug batch does not exist.',

            'items.*.quantity.required' =>
            'The quantity is required for each sale item.',

            'items.*.quantity.min' =>
            'The quantity must be at least 1.',

            'payments.required' =>
            'At least one payment is required.',

            'payments.min' =>
            'A sale must contain at least one payment.',

            'payments.*.payment_method.required' =>
            'A payment method is required.',

            'payments.*.amount.required' =>
            'The payment amount is required.',

            'payments.*.amount.gt' =>
            'The payment amount must be greater than zero.',
        ];
    }

    /**
     * Customize the request attributes.
     */
    public function attributes(): array
    {
        return [
            'customer_id' =>
            'customer',

            'items.*.drug_batch_id' =>
            'drug batch',

            'items.*.quantity' =>
            'quantity',

            'payments.*.payment_method' =>
            'payment method',

            'payments.*.amount' =>
            'payment amount',
        ];
    }
}
