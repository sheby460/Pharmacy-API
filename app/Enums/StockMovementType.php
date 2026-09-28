<?php

namespace App\Enums;

enum StockMovementType: string
{
    /**
     * Stock received from a supplier purchase.
     */
    case PURCHASE = 'PURCHASE';

    /**
     * Stock deducted when a sale is completed.
     */
    case SALE = 'SALE';

    /**
     * Stock restored because a customer returned a sale item.
     */
    case SALE_RETURN = 'SALE_RETURN';

    /**
     * Stock restored because a completed sale was cancelled.
     */
    case SALE_CANCELLATION = 'SALE_CANCELLATION';

    /**
     * Stock returned to a supplier.
     */
    case PURCHASE_RETURN = 'PURCHASE_RETURN';

    /**
     * Stock added through a manual adjustment.
     */
    case ADJUSTMENT_IN = 'ADJUSTMENT_IN';

    /**
     * Stock removed through a manual adjustment.
     */
    case ADJUSTMENT_OUT = 'ADJUSTMENT_OUT';

    /**
     * Stock removed because of damage.
     */
    case DAMAGE = 'DAMAGE';

    /**
     * Stock removed because it expired.
     */
    case EXPIRED = 'EXPIRED';

    /**
     * Stock restored because a purchase was cancelled.
     */
    case PURCHASE_CANCELLATION = 'PURCHASE_CANCELLATION';
}