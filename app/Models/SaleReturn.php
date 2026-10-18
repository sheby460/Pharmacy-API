<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    protected $fillable = [
        'return_number',
        'sale_id',
        'processed_by',
        'status',
        'refund_method',
        'refund_status',
        'refund_amount',
        'reason',
        'notes',
        'refunded_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'refunded_at' => 'datetime',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function isRefunded(): bool
    {
        return $this->refund_status === 'REFUNDED';
    }
    public function returnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class, 'sale_item_id');
    }
}
