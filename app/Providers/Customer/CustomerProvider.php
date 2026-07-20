<?php

namespace App\Providers\Customer;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Customers\CustomerRepository;
use App\Repositories\Customers\CustomerRepositoryInterface;

class CustomerProvider extends ServiceProvider
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
