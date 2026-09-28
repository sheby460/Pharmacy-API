<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roles' => [
                'required',
                'array',
                'min:1',
            ],

            'roles.*' => [
                'required',
                'string',
                Rule::exists('roles', 'name')
                    ->where(
                        fn ($query) => $query->where(
                            'guard_name',
                            'web'
                        )
                    ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'roles.required' =>
                'At least one role must be assigned.',

            'roles.array' =>
                'Roles must be an array.',

            'roles.min' =>
                'At least one role must be assigned.',

            'roles.*.exists' =>
                'One or more selected roles do not exist.',
        ];
    }
}