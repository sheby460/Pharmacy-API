<?php

namespace App\Http\Resources\Role;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,

            'permissions' => $this->when(
                $this->relationLoaded('permissions'),
                fn () => $this->permissions->map(
                    fn ($permission) => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                    ]
                )->values()
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}