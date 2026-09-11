<?php

namespace App\Providers\SubCategoryProvider;

use App\Repositories\SubCategoryRepository\SubCategoryInterface;
use App\Repositories\SubCategoryRepository\SubCategoryRepository;
use Illuminate\Support\ServiceProvider;

class SubCategoryProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            SubCategoryInterface::class,
            SubCategoryRepository::class
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
