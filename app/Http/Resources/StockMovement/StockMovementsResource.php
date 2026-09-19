<?php

namespace App\Http\Resources\StockMovement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementsResource
    extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [
            'id' => $this->id,

            'drug_id' =>
                $this->drug_id,

            'drug_batch_id' =>
                $this->drug_batch_id,

            'created_by' =>
                $this->created_by,

            'movement_type' =>
                $this->movement_type instanceof \BackedEnum
                    ? $this->movement_type->value
                    : $this->movement_type,

            'quantity' =>
                (int) $this->quantity,

            'quantity_before' =>
                (int) $this->quantity_before,

            'quantity_after' =>
                (int) $this->quantity_after,

            'reference_type' =>
                $this->reference_type,

            'reference_id' =>
                $this->reference_id,

            'notes' =>
                $this->notes,

            'drug' => $this->whenLoaded(
                'drug',
                fn () => [
                    'id' =>
                        $this->drug->id,

                    'drug_name' =>
                        $this->drug->drug_name,

                    'drug_code' =>
                        $this->drug->drug_code,
                ]
            ),

            'drug_batch' => $this->whenLoaded(
                'drugBatch',
                fn () => [
                    'id' =>
                        $this->drugBatch->id,

                    'batch_number' =>
                        $this->drugBatch
                            ->batch_number,

                    'expiry_date' =>
                        $this->drugBatch
                            ->expiry_date
                            ?->toDateString(),
                ]
            ),

            'created_by_user' =>
                $this->whenLoaded(
                    'createdBy',
                    fn () => [
                        'id' =>
                            $this->createdBy->id,

                        'name' =>
                            $this->createdBy->name,
                    ]
                ),

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),
        ];
    }
}