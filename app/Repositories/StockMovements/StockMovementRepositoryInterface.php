<?php

namespace App\Repositories\StockMovements;

use App\Models\StockMovement;
use Illuminate\Pagination\LengthAwarePaginator;

interface StockMovementRepositoryInterface
{
    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?string $type = null,
        ?int $drugId = null
    ): LengthAwarePaginator;

    public function findById(
        int $id
    ): StockMovement;
}