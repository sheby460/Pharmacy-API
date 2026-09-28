<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    protected $fillable = [
        'sale_id',
        'payment_method',
        'amount',
        'reference',
        'notes',
    ];

    protected $casts = [
        'payment_method' => PaymentMethod::class,
        'amount' => 'decimal:2',
    ];



    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}