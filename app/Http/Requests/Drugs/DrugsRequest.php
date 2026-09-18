<?php

namespace App\Http\Requests\Drugs;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DrugsRequest extends FormRequest
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
            'drug_code' => 'required|string|max:255',
            'sub_category_id' => 'required|integer|exists:sub_categories,id',
            'category_id' => 'required|integer|exists:categories,id',
            'drug_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'strength' => 'nullable|string|max:255',
            'dosage_form' => 'nullable|string|max:255',
            'reorder_level' => 'nullable|integer|min:0',
            'unit' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:0',
            'purchasing_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'expiry_date' => 'required|date_format:Y-m-d',
            'is_active' => 'nullable|boolean',
        ];
    }
}
