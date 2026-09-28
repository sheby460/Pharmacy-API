<?php
namespace App\Http\Resources\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalePaymentResource extends JsonResource
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

            'payment_method' => $this->payment_method?->value,

            'amount' => $this->amount,

            'reference' => $this->reference,

            'notes' => $this->notes,

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}