<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Drug extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'drug_code',
        'sub_category_id',
        'category_id',
        'drug_name',
        'description',
        'generic_name',
        'manufacturer',
        'strength',
        'dosage_form',
        'reorder_level',
        'unit',
        'barcode',
        'quantity',
        'purchasing_price',
        'selling_price',
        'expiry_date',
        'is_active',
    ];

    protected $casts = [
        'expiry_date'       => 'date',
        'is_active'         => 'boolean',
        'quantity'          => 'integer',
        'reorder_level'     => 'integer',
        'purchasing_price'  => 'decimal:2',
        'selling_price'     => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }
}