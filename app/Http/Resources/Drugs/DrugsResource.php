<?php

namespace App\Http\Resources\Drugs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrugsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'drug_code'         => $this->drug_code,
            'drug_name'         => $this->drug_name,
            'generic_name'      => $this->generic_name,
            'description'       => $this->description,
            'manufacturer'      => $this->manufacturer,
            'strength'          => $this->strength,
            'dosage_form'       => $this->dosage_form,
            'unit'              => $this->unit,
            'barcode'           => $this->barcode,
            'quantity'          => $this->quantity,
            'reorder_level'     => $this->reorder_level,
            'purchasing_price'  => $this->purchasing_price,
            'selling_price'     => $this->selling_price,
            'expiry_date'       => $this->expiry_date?->toDateString(),
            'is_active'         => (bool) $this->is_active,

            'category_id'       => $this->category_id,
            'sub_category_id'   => $this->sub_category_id,

            'category_name'     => $this->category?->category_name,
            'sub_category_name' => $this->subCategory?->sub_category_name,

            'created_at'        => $this->created_at?->toDateTimeString(),
            'updated_at'        => $this->updated_at?->toDateTimeString(),
            'deleted_at'        => $this->deleted_at?->toDateTimeString(),
        ];
    }
}