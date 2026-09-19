<?php

namespace App\Enums;

enum StockMovementType: string
{
    case PURCHASE = 'PURCHASE';
    case SALE = 'SALE';
    case SALE_RETURN = 'SALE_RETURN';
    case PURCHASE_RETURN = 'PURCHASE_RETURN';
    case ADJUSTMENT_IN = 'ADJUSTMENT_IN';
    case ADJUSTMENT_OUT = 'ADJUSTMENT_OUT';
    case DAMAGE = 'DAMAGE';
    case EXPIRED = 'EXPIRED';
}