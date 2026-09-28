<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'fname' => $this->fname,
            'mname' => $this->mname,
            'lname' => $this->lname,

            'username' => $this->username,
            'email' => $this->email,
            'gender' => $this->gender,
            'phone' => $this->phone,

            'is_active' => (bool) $this->is_active,

            'roles' => $this->whenLoaded(
                'roles',
                fn () => $this->roles
                    ->map(fn ($role) => [
                        'id' => $role->id,
                        'name' => $role->name,
                    ])
                    ->values()
                    ->all()
            ),

            'permissions' => $this->when(
                $this->relationLoaded('roles'),
                fn () => $this->getAllPermissions()
                    ->pluck('name')
                    ->unique()
                    ->values()
                    ->all()
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}