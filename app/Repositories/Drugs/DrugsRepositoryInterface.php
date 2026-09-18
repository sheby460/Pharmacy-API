<?php

namespace App\Repositories\Drugs;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Drug;

interface DrugsRepositoryInterface
{
   public function getAll(): Collection;

   public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): Drug;

    public function create(array $data);

    public function update(Drug $drug, array $data);

    public function delete(Drug $drug);
}