<?php
namespace App\Repositories\SubCategoryRepository;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\SubCategory;

interface SubCategoryInterface {
    public function getAll(): Collection;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): SubCategory;

    public function create(array $data): SubCategory;

    public function update(SubCategory $subCategory, array $data): SubCategory;

    public function delete(SubCategory $subCategory): bool;


}