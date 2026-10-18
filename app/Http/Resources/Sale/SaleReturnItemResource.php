<?php

namespace App\Http\Resources\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_item_id' => $this->sale_item_id,
            'drug_id' => $this->drug_id,
            'drug_batch_id' => $this->drug_batch_id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'line_total' => $this->line_total,
            'disposition' => $this->disposition,
            'notes' => $this->notes,

            'drug' => $this->whenLoaded('drug', function () {
                return [
                    'id' => $this->drug->id,
                    'drug_name' => $this->drug->drug_name ?? null,
                    'drug_code' => $this->drug->drug_code ?? null,
                ];
            }),

            'batch' => $this->whenLoaded('drugBatch', function () {
                return [
                    'id' => $this->drugBatch->id,
                    'batch_number' => $this->drugBatch->batch_number,
                    'expiry_date' => $this->drugBatch->expiry_date,
                ];
            }),
        ];
    }
}
