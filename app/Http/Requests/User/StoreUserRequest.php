<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
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
                'unique:users,username',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email',
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

            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' =>
                'This username is already registered.',

            'username.alpha_dash' =>
                'The username may only contain letters, numbers, dashes and underscores.',

            'email.unique' =>
                'This email is already registered.',

            'password.confirmed' =>
                'Password confirmation does not match.',
        ];
    }
}