<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Drug extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'drug_code',
        'category_id',
        'sub_category_id',
        'drug_name',
        'generic_name',
        'description',
        'manufacturer',
        'strength',
        'dosage_form',
        'unit',
        'barcode',
        'reorder_level',
        'selling_price',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'reorder_level' => 'integer',
        'selling_price' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(DrugBatch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}