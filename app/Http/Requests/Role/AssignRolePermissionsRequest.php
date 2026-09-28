<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => [
                'required',
                'array',
            ],

            'permissions.*' => [
                'required',
                'string',
                Rule::exists('permissions', 'name')
                    ->where('guard_name', 'web'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.required' =>
                'Permissions are required.',

            'permissions.array' =>
                'Permissions must be an array.',

            'permissions.*.exists' =>
                'One or more selected permissions do not exist.',
        ];
    }
}