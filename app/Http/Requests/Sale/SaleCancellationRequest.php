<?php

namespace App\Http\Requests\Sale;

use Illuminate\Foundation\Http\FormRequest;

class SaleCancellationRequest extends FormRequest
{
    /**
     * Only authenticated users may submit this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Normalize the cancellation reason before validation.
     */
    protected function prepareForValidation(): void
    {
        $reason = $this->input('reason');

        if (is_string($reason)) {
            $this->merge([
                'reason' => trim($reason),
            ]);
        }
    }

    /**
     * Validate the cancellation reason.
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
     * Return clear validation messages.
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'A cancellation reason is required.',
            'reason.string' => 'The cancellation reason must be text.',
            'reason.min' => 'The cancellation reason must contain at least 3 characters.',
            'reason.max' => 'The cancellation reason cannot exceed 1000 characters.',
        ];
    }
}
