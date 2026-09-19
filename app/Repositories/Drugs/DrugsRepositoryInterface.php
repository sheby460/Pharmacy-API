<?php

namespace App\Repositories\Drugs;

use App\Models\Drug;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface DrugsRepositoryInterface
{
    public function getAll(): Collection;

    public function paginate(
        int $perPage = 15,
        ?string $search = null
    ): LengthAwarePaginator;

    public function findById(int $id): Drug;

    public function create(array $data);

    public function update(
        Drug $drug,
        array $data
    );

    public function delete(Drug $drug);
}