<?php

namespace App\Http\Resources\Purchase;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier' => $this->whenLoaded('supplier', function () {
                return [
                    'id' => $this->supplier->id,
                    'name' => $this->supplier->supplier_name,
                ];
            }),

            'invoice_number' => $this->invoice_number,
            'purchase_date' => $this->purchase_date?->toDateString(),
            'status' => $this->status?->value,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->total,
            'notes' => $this->notes,

            'created_by' => $this->whenLoaded('createdBy', function () {
                return $this->createdBy
                    ? [
                        'id' => $this->createdBy->id,
                        'name' => trim(implode(' ', array_filter([
                            $this->createdBy->fname,
                            $this->createdBy->mname,
                            $this->createdBy->lname,
                        ]))) ?: $this->createdBy->username,
                    ]
                    : null;
            }),

            'received_by' => $this->whenLoaded('receivedBy', function () {
                return $this->receivedBy
                    ? [
                        'id' => $this->receivedBy->id,
                        'name' => trim(implode(' ', array_filter([
                            $this->receivedBy->fname,
                            $this->receivedBy->mname,
                            $this->receivedBy->lname,
                        ]))) ?: $this->receivedBy->username,
                    ]
                    : null;
            }),

            'received_at' => $this->received_at,
            'cancelled_by' => $this->whenLoaded('cancelledBy', function () {
                return $this->cancelledBy
                    ? [
                        'id' => $this->cancelledBy->id,
                         'name' => trim(implode(' ', array_filter([
                            $this->cancelledBy->fname,
                            $this->cancelledBy->mname,
                            $this->cancelledBy->lname,
                        ]))) ?: $this->cancelledBy->username,
                    ]
                    : null;
            }),

            'cancelled_at' => $this->cancelled_at,

            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(fn($item) => [
                    'id' => $item->id,
                    'drug_id' => $item->drug_id,
                    'drug' => $item->relationLoaded('drug') && $item->drug
                        ? [
                            'id' => $item->drug->id,
                            'name' => $item->drug->name,
                        ]
                        : null,
                    'drug_batch_id' => $item->drug_batch_id,
                    'batch_number' => $item->batch_number,
                    'expiry_date' => $item->expiry_date?->toDateString(),
                    'purchase_price' => $item->purchase_price,
                    'selling_price' => $item->selling_price,
                    'quantity_received' => $item->quantity_received,
                    'line_total' => $item->line_total,
                ]);
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
