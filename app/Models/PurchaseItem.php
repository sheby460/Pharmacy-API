<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'drug_id',
        'drug_batch_id',
        'batch_number',
        'expiry_date',
        'purchase_price',
        'selling_price',
        'quantity_received',
        'line_total',
    ];

    protected $casts = [
        'expiry_date' => 'date',

        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'line_total' => 'decimal:2',

        'quantity_received' => 'integer',
    ];

    /**
     * Purchase that owns this item.
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(
            Purchase::class
        );
    }

    /**
     * Drug being purchased.
     */
    public function drug(): BelongsTo
    {
        return $this->belongsTo(
            Drug::class
        );
    }

    /**
     * Drug batch associated with this item.
     */
    public function drugBatch(): BelongsTo
    {
        return $this->belongsTo(
            DrugBatch::class
        );
    }
}