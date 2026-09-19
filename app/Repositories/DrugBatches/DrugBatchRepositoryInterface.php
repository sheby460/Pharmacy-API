<?php

namespace App\Repositories\DrugBatches;

use App\Models\DrugBatch;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface DrugBatchRepositoryInterface
{
    public function getAll(): Collection;

    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?int $drugId = null
    ): LengthAwarePaginator;

    public function findById(int $id): DrugBatch;

    public function getByDrug(
        int $drugId,
        int $perPage = 15
    ): LengthAwarePaginator;

    public function create(array $data): DrugBatch;

    public function getSellableBatches(
        int $drugId
    ): Collection;
}