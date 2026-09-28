<?php

namespace App\Enums;

enum SaleStatus: string
{
    case COMPLETED = 'COMPLETED';

    case CANCELLED = 'CANCELLED';

    /**
     * Determine whether the sale is completed.
     */
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Determine whether the sale is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    /**
     * Determine whether the sale can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this === self::COMPLETED;
    }
}