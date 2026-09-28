<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id
            ?? $this->route('user');

        return [
            'fname' => [
                'required',
                'string',
                'max:100',
            ],

            'mname' => [
                'nullable',
                'string',
                'max:100',
            ],

            'lname' => [
                'required',
                'string',
                'max:100',
            ],

            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')
                    ->ignore($userId),
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($userId),
            ],

            'gender' => [
                'nullable',
                Rule::in([
                    'male',
                    'female',
                ]),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],
        ];
    }
}