<?php

namespace App\Http\Requests\Sale;

use Illuminate\Foundation\Http\FormRequest;

class SaleCancellationRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized
     * to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules.
     */
    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:3',
                'max:1000',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    //public function messages(): array
    // {
    //     return [
    //         'reason.required' =>
    //             'A cancellation reason is required.',

    //         'reason.min' =>
    //             'The cancellation reason must contain at least 3 characters.',

    //         'reason.max' =>
    //             'The cancellation reason cannot exceed 1000 characters.',
    //     ];
    // }

     protected function prepareForValidation(): void
    {
        if ($this->has('reason')) {
            $this->merge([
                'reason' => trim((string) $this->input('reason')),
            ]);
        }
    }
}