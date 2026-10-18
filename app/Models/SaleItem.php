<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'drug_id',
        'drug_batch_id',

        // Historical snapshots for receipts and traceability.
        'drug_name_snapshot',
        'drug_code_snapshot',
        'batch_number_snapshot',
        'expiry_date_snapshot',

        'quantity',
        'unit_price',
        'discount',
        'tax',
        'subtotal',
        'total',
    ];

    protected $casts = [
        'quantity' => 'integer',

        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'expiry_date_snapshot' => 'date',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    public function drugBatch(): BelongsTo
    {
        return $this->belongsTo(DrugBatch::class);
    }
}