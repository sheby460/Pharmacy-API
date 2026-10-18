<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    /**
     * Determine whether the user can cancel a sale.
     *
     * The route middleware is a first guard; the policy keeps the same
     * permission rule in force when authorization is invoked elsewhere.
     */
    public function cancel(User $user, Sale $sale): bool
    {
        return $sale->canBeCancelled()
        && $user->can('sales.cancel');
    }
}
