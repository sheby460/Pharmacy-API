<?php

namespace App\Http\Resources\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'drug' => $this->whenLoaded(
                'drug',
                fn () => $this->drug
                    ? [
                        'id' => $this->drug->id,
                        'drug_code' => $this->drug->drug_code,
                        'drug_name' => $this->drug->drug_name,
                    ]
                    : null
            ),

            'batch' => $this->whenLoaded(
                'drugBatch',
                fn () => $this->drugBatch
                    ? [
                        'id' => $this->drugBatch->id,
                        'batch_number' => $this->drugBatch->batch_number,
                        'expiry_date' => $this->drugBatch->expiry_date
                            ?->toDateString(),
                    ]
                    : null
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