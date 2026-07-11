<?php

namespace App\Providers\Costomer;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Customers\CustomerRepository;
use App\Repositories\Customers\CustomerRepositoryInterface;

class CostomerProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            CustomerRepositoryInterface::class,
            CustomerRepository::class
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
