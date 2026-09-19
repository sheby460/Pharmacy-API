<?php

namespace App\Http\Resources\DrugBatch;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrugBatchResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        $available =
            (int) $this->quantity_available;

        $expired =
            $this->expiry_date?->isPast() ?? false;

        return [
            'id' => $this->id,

            'drug_id' => $this->drug_id,
            'supplier_id' => $this->supplier_id,

            'batch_number' =>
                $this->batch_number,

            'expiry_date' =>
                $this->expiry_date?->toDateString(),

            'purchase_price' =>
                $this->purchase_price,

            'selling_price' =>
                $this->selling_price,

            'quantity_received' =>
                (int) $this->quantity_received,

            'quantity_available' =>
                $available,

            'received_at' =>
                $this->received_at
                    ?->toDateTimeString(),

            'is_expired' => $expired,

            'stock_status' => match (true) {
                $expired => 'EXPIRED',
                $available <= 0 => 'OUT_OF_STOCK',
                default => 'AVAILABLE',
            },

            'drug' => $this->whenLoaded(
                'drug',
                function () {
                    return [
                        'id' =>
                            $this->drug->id,

                        'drug_name' =>
                            $this->drug->drug_name,

                        'drug_code' =>
                            $this->drug->drug_code,
                    ];
                }
            ),

            'supplier' => $this->whenLoaded(
                'supplier',
                function () {
                    return [
                        'id' =>
                            $this->supplier->id,

                        'supplier_name' =>
                            $this->supplier
                                ->supplier_name,
                    ];
                }
            ),

            'created_at' =>
                $this->created_at
                    ?->toDateTimeString(),

            'updated_at' =>
                $this->updated_at
                    ?->toDateTimeString(),
        ];
    }
}