<?php

namespace App\Http\Requests\StockMovements;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends FormRequest
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
     * Get the validation rules.
     */
    public function rules(): array
    {
        return [
            /**
             * Drug batch to be adjusted.
             */
            'drug_batch_id' => [
                'required',
                'integer',
                'exists:drug_batches,id',
            ],

            /**
             * Supported adjustment movement types.
             */
            'movement_type' => [
                'required',
                'string',
                Rule::in([
                    'ADJUSTMENT_IN',
                    'ADJUSTMENT_OUT',
                    'DAMAGE',
                    'EXPIRED',
                ]),
            ],

            /**
             * Quantity must be a positive integer.
             */
            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            /**
             * Notes are required for damage
             * and expired stock.
             */
            'notes' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf(function (): bool {
                    return in_array(
                        $this->input('movement_type'),
                        [
                            'DAMAGE',
                            'EXPIRED',
                        ],
                        true
                    );
                }),
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'drug_batch_id.required' =>
                'Please select a drug batch.',

            'drug_batch_id.integer' =>
                'The selected drug batch is invalid.',

            'drug_batch_id.exists' =>
                'The selected drug batch does not exist.',

            'movement_type.required' =>
                'Please select a movement type.',

            'movement_type.in' =>
                'The selected movement type is invalid.',

            'quantity.required' =>
                'Please enter the stock quantity.',

            'quantity.integer' =>
                'Stock quantity must be a whole number.',

            'quantity.min' =>
                'Stock quantity must be at least 1.',

            'notes.required' =>
                'Notes are required for damage or expired stock.',

            'notes.string' =>
                'Notes must be a valid text value.',

            'notes.max' =>
                'Notes cannot exceed 1000 characters.',
        ];
    }

    /**
     * Prepare request data before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'drug_batch_id' => $this->filled('drug_batch_id')
                ? (int) $this->input('drug_batch_id')
                : null,

            'quantity' => $this->filled('quantity')
                ? (int) $this->input('quantity')
                : null,

            'movement_type' => $this->filled('movement_type')
                ? strtoupper(trim((string) $this->input('movement_type')))
                : null,

            'notes' => $this->filled('notes')
                ? trim((string) $this->input('notes'))
                : null,
        ]);
    }
}