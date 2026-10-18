<?php

namespace App\Http\Resources\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'return_number' => $this->return_number,
            'sale_id' => $this->sale_id,
            'status' => $this->status,
            'refund_method' => $this->refund_method,
            'refund_status' => $this->refund_status,
            'refund_amount' => $this->refund_amount,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'refunded_at' => $this->refunded_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),

            'sale' => $this->whenLoaded('sale', function () {
                return [
                    'id' => $this->sale->id,
                    'invoice_number' => $this->sale->invoice_number,
                    'status' => $this->sale->status instanceof \BackedEnum
                        ? $this->sale->status->value
                        : $this->sale->status,
                ];
            }),

            'processed_by' => $this->whenLoaded('processedBy', function () {
                return [
                    'id' => $this->processedBy->id,
                    'fname' => $this->processedBy->fname,
                    'mname' => $this->processedBy->mname,
                    'lname' => $this->processedBy->lname,
                    'username' => $this->processedBy->username,
                ];
            }),

            'items' => $this->whenLoaded(
                'items',
                fn() => SaleReturnItemResource::collection($this->items)
            ),
        ];
    }
}
