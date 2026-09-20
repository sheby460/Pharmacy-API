<?php

namespace App\Providers\StockMovements;

use App\Repositories\StockMovements\StockMovementRepository;
use App\Repositories\StockMovements\StockMovementRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class StockMovementProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            StockMovementRepositoryInterface::class,
            StockMovementRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
