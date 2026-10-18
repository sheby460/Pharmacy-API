<?php

namespace App\Http\Resources\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'drug' => $this->whenLoaded(
                'drug',
                fn () => $this->drug
                    ? [
                        'id' => $this->drug_id,
                        'drug_code' => $this->drug_code_snapshot
                            ?? $this->drug->drug_code,
                        'drug_name' => $this->drug_name_snapshot
                            ?? $this->drug->drug_name,
                    ]
                    : [
                        'id' => $this->drug_id,
                        'drug_code' => $this->drug_code_snapshot,
                        'drug_name' => $this->drug_name_snapshot,
                    ]
            ),

            'batch' => $this->whenLoaded(
                'drugBatch',
                fn () => $this->drugBatch
                    ? [
                        'id' => $this->drug_batch_id,
                        'batch_number' => $this->batch_number_snapshot
                            ?? $this->drugBatch->batch_number,
                        'expiry_date' => $this->expiry_date_snapshot
                            ?->toDateString()
                            ?? $this->drugBatch->expiry_date?->toDateString(),
                    ]
                    : [
                        'id' => $this->drug_batch_id,
                        'batch_number' => $this->batch_number_snapshot,
                        'expiry_date' => $this->expiry_date_snapshot
                            ?->toDateString(),
                    ]
            ),

            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
        ];
    }
}
