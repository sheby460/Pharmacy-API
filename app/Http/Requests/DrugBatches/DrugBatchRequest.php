<?php

namespace App\Http\Requests\DrugBatches;

use App\Models\DrugBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DrugBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'drug_id' => [
                'required',
                'integer',
                'exists:drugs,id',
            ],

            'supplier_id' => [
                'required',
                'integer',
                'exists:suppliers,id',
            ],

            'batch_number' => [
                'required',
                'string',
                'max:100',

                Rule::unique(
                    'drug_batches',
                    'batch_number'
                )->where(function ($query) {
                    return $query->where(
                        'drug_id',
                        $this->drug_id
                    );
                }),
            ],

            'expiry_date' => [
                'required',
                'date_format:Y-m-d',
                'after:today',
            ],

            'purchase_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'selling_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'quantity_received' => [
                'required',
                'integer',
                'min:1',
            ],

            'received_at' => [
                'nullable',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'drug_id.exists' =>
                'The selected drug does not exist.',

            'supplier_id.exists' =>
                'The selected supplier does not exist.',

            'batch_number.required' =>
                'Batch number is required.',

            'batch_number.unique' =>
                'This batch number already exists for this drug.',

            'expiry_date.after' =>
                'The expiry date must be in the future.',

            'quantity_received.min' =>
                'Received quantity must be at least 1.',
        ];
    }
}