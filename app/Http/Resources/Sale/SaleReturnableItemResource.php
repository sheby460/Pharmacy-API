<?php

namespace App\Http\Resources\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnableItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sold = (int) $this->quantity;
        $returned = (int) ($this->returned_quantity ?? 0);

        return [
            'sale_item_id' => $this->id,
            'drug_id' => $this->drug_id,
            'drug_batch_id' => $this->drug_batch_id,

            'drug_name' => $this->drug_name_snapshot
                ?? $this->drug?->drug_name,

            'drug_code' => $this->drug_code_snapshot
                ?? $this->drug?->drug_code,

            'batch_number' => $this->batch_number_snapshot
                ?? $this->drugBatch?->batch_number,

            'expiry_date' => $this->expiry_date_snapshot
                ?? $this->drugBatch?->expiry_date?->toDateString(),

            'quantity_sold' => $sold,
            'quantity_returned' => $returned,
            'quantity_returnable' => max(0, $sold - $returned),

            'unit_price' => $this->unit_price,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->total,
        ];
    }
}