<?php

namespace App\Http\Requests\StockMovements;

use App\Enums\StockMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'drug_batch_id' => [
                'required',
                'integer',
                'exists:drug_batches,id',
            ],

            'movement_type' => [
                'required',
                Rule::in([
                    StockMovementType::ADJUSTMENT_IN->value,
                    StockMovementType::ADJUSTMENT_OUT->value,
                ]),
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}