<?php

namespace App\Http\Requests\Supplier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'supplier_name' => 'required|string','max:255',
            'location' => 'nullable|string','max:255',
            'address' => 'nullable|string','max:255',
            'contacts' => 'nullable|string','max:255',
            'tax_ID' => 'nullable|string', 'max:255',
        ];
    }

    public function message(): array{
        return [
            'supplier_name.required' => 'Supplier name is required',
        ];
    }
}
