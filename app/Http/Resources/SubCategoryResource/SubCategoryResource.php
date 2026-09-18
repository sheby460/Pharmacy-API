<?php

namespace App\Http\Resources\SubCategoryResource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubCategoryResource extends JsonResource
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
            'sub_category_name' => $this->sub_category_name,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'category' => $this->when(
                $this->relationLoaded('category'),
                function () {
                    return [
                        'id' => $this->category->id,
                        'category_name' => $this->category->category_name,
                    ];
                }
            )
        ];
    }
}