<?php

namespace App\Providers\DrugBatch;
use App\Repositories\DrugBatches\DrugBatchRepository;
use App\Repositories\DrugBatches\DrugBatchRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class DrugBatchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            DrugBatchRepositoryInterface::class,
            DrugBatchRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}