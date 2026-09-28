<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    /**
     * Determine whether the user can cancel a sale.
     */
    public function cancel(User $user, Sale $sale): bool
    {
        /*
         * Replace this with your actual permission system.
         *
         * Example:
         *
         * return $user->hasPermission('sales.cancel');
         */

        return true;
    }
}