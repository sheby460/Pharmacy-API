<?php

namespace App\Http\Resources\AuthResource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'fname' => $this->fname,
            'mname' => $this->mname,
            'lname' => $this->lname,
            'username' => $this->username,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'email' => $this->email,
            
        ];
    }
}
