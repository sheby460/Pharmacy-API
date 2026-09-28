<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'supplier_id',
        'created_by',
        'invoice_number',
        'purchase_date',
        'status',
        'subtotal',
        'discount',
        'tax',
        'total',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'status' => PurchaseStatus::class,
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Supplier who provided the purchase.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(
            Supplier::class
        );
    }

    /**
     * User who created the purchase.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /**
     * Items included in the purchase.
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            PurchaseItem::class
        );
    }

    /**
     * Determine whether the purchase is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === PurchaseStatus::DRAFT;
    }

    /**
     * Determine whether the purchase has been received.
     */
    public function isReceived(): bool
    {
        return $this->status === PurchaseStatus::RECEIVED;
    }

    /**
     * Determine whether the purchase is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === PurchaseStatus::CANCELLED;
    }
}