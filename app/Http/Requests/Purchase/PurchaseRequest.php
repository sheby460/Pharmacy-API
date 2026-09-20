<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                'integer',
                'exists:suppliers,id',
            ],

            'invoice_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'purchase_date' => [
                'required',
                'date',
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
                'max:2000',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.drug_id' => [
                'required',
                'integer',
                'exists:drugs,id',
            ],

            'items.*.batch_number' => [
                'required',
                'string',
                'max:100',
            ],

            'items.*.expiry_date' => [
                'required',
                'date',
            ],

            'items.*.purchase_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items.*.selling_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items.*.quantity_received' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'supplier_id.required' =>
                'Please select a supplier.',

            'supplier_id.exists' =>
                'The selected supplier does not exist.',

            'purchase_date.required' =>
                'Please select the purchase date.',

            'items.required' =>
                'Please add at least one purchase item.',

            'items.min' =>
                'A purchase must contain at least one item.',

            'items.*.drug_id.required' =>
                'Please select a drug for every item.',

            'items.*.drug_id.exists' =>
                'One of the selected drugs does not exist.',

            'items.*.batch_number.required' =>
                'Batch number is required for every item.',

            'items.*.expiry_date.required' =>
                'Expiry date is required for every item.',

            'items.*.purchase_price.required' =>
                'Purchase price is required for every item.',

            'items.*.selling_price.required' =>
                'Selling price is required for every item.',

            'items.*.quantity_received.required' =>
                'Quantity is required for every item.',

            'items.*.quantity_received.min' =>
                'Each item quantity must be at least 1.',
        ];
    }

    /**
     * Prepare normalized request values.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'invoice_number' => $this->filled('invoice_number')
                ? trim((string) $this->input('invoice_number'))
                : null,

            'discount' => $this->filled('discount')
                ? (float) $this->input('discount')
                : 0,

            'tax' => $this->filled('tax')
                ? (float) $this->input('tax')
                : 0,

            'notes' => $this->filled('notes')
                ? trim((string) $this->input('notes'))
                : null,
        ]);
    }
}