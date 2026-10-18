<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrugBatch extends Model
{
    protected $fillable = [
        'drug_id',
        'supplier_id',
        'batch_number',
        'expiry_date',
        'purchase_price',
        'selling_price',
        'quantity_received',
        'quantity_available',
        'received_at',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'received_at' => 'datetime',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'quantity_received' => 'integer',
        'quantity_available' => 'integer',
    ];

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * A batch is expired only after its expiry date has passed.
     * A missing expiry date is considered unsafe.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date === null
            || $this->expiry_date->lt(today());
    }

    /**
     * Determine whether this batch has stock available for dispensing.
     */
    public function hasAvailableStock(): bool
    {
        return $this->quantity_available > 0
            && ! $this->isExpired();
    }

    /**
     * Scope batches that can be dispensed today.
     */
    public function scopeAvailableForSale(Builder $query): Builder
    {
        return $query
            ->where('quantity_available', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', today()->toDateString());
    }
    }
    //  {
    //     return $this->quantity_available > 0;
    // }

  
