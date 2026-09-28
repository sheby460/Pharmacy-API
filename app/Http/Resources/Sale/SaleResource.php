<?php

namespace App\Http\Resources\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Sale Information
            |--------------------------------------------------------------------------
            */

            'id' => $this->id,

            'invoice_number' => $this->invoice_number,

            'status' => $this->status?->value,

            'notes' => $this->notes,

            /*
            |--------------------------------------------------------------------------
            | Customer Information
            |--------------------------------------------------------------------------
            */

            'customer' => $this->whenLoaded(
                'customer',
                fn () => $this->customer
                    ? [
                        'id' => $this->customer->id,
                        'name' => $this->customer->name,
                    ]
                    : null
            ),

            /*
            |--------------------------------------------------------------------------
            | User Information
            |--------------------------------------------------------------------------
            */

            'created_by' => $this->whenLoaded(
                'createdBy',
                fn () => $this->createdBy
                    ? [
                        'id' => $this->createdBy->id,
                        'name' => $this->createdBy->name,
                    ]
                    : null
            ),

            'cancelled_by' => $this->whenLoaded(
                'cancelledBy',
                fn () => $this->cancelledBy
                    ? [
                        'id' => $this->cancelledBy->id,
                        'name' => $this->cancelledBy->name,
                    ]
                    : null
            ),

            /*
            |--------------------------------------------------------------------------
            | Financial Information
            |--------------------------------------------------------------------------
            */

            'subtotal' => $this->subtotal,

            'discount' => $this->discount,

            'tax' => $this->tax,

            'total' => $this->total,

            /*
            |--------------------------------------------------------------------------
            | Cancellation Information
            |--------------------------------------------------------------------------
            */

            'cancelled_at' => $this->cancelled_at?->toISOString(),

            'cancellation_reason' => $this->cancellation_reason,

            /*
            |--------------------------------------------------------------------------
            | Aggregates
            |--------------------------------------------------------------------------
            */

            'items_count' => $this->when(
                isset($this->items_count),
                fn () => $this->items_count
            ),

            'payments_count' => $this->when(
                isset($this->payments_count),
                fn () => $this->payments_count
            ),

            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */

            'items' => SaleItemResource::collection(
                $this->whenLoaded('items')
            ),

            'payments' => SalePaymentResource::collection(
                $this->whenLoaded('payments')
            ),

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            'created_at' => $this->created_at?->toISOString(),

            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}