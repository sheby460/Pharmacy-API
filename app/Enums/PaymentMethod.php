<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'CASH';
    case MOBILE_MONEY = 'MOBILE MONEY';
    case CARD = 'CARD';
}