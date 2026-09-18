<?php

namespace App\Providers\Drugs;

use App\Repositories\Drugs\DrugsRepository;
use App\Repositories\Drugs\DrugsRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class DrugsProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            
            DrugsRepositoryInterface::class,
            DrugsRepository::class
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
