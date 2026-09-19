<?php

namespace App\Http\Requests\Drugs;

use App\Models\Drug;
use App\Models\SubCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DrugsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $drug = $this->route('drug');

        $drugId = $drug instanceof Drug
            ? $drug->id
            : $this->route('id');

        return [
            'drug_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('drugs', 'drug_code')
                    ->ignore($drugId),
            ],

            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],

            'sub_category_id' => [
                'required',
                'integer',
                'exists:sub_categories,id',
            ],

            'drug_name' => [
                'required',
                'string',
                'max:255',
            ],

            'generic_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'manufacturer' => [
                'nullable',
                'string',
                'max:255',
            ],

            'strength' => [
                'nullable',
                'string',
                'max:255',
            ],

            'dosage_form' => [
                'nullable',
                'string',
                'max:255',
            ],

            'unit' => [
                'nullable',
                'string',
                'max:255',
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('drugs', 'barcode')
                    ->ignore($drugId),
            ],

            'reorder_level' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'selling_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $categoryId = $this->input('category_id');
            $subCategoryId = $this->input('sub_category_id');

            if (!$categoryId || !$subCategoryId) {
                return;
            }

            $belongsToCategory = SubCategory::where('id', $subCategoryId)
                ->where('category_id', $categoryId)
                ->exists();

            if (!$belongsToCategory) {
                $validator->errors()->add(
                    'sub_category_id',
                    'The selected subcategory does not belong to the selected category.'
                );
            }
        });
    }
}