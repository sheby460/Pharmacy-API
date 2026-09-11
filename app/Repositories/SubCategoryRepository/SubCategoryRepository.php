<?php

namespace App\Repositories\SubCategoryRepository;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\SubCategory;

class SubCategoryRepository implements SubCategoryInterface
{
    public function getAll(): Collection
    {
        return SubCategory::query()->latest()->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return SubCategory::query()->latest()->paginate($perPage);
    }

    public function findById(int $id): SubCategory
    {
        return SubCategory::query()->findOrFail($id);
    }

    public function create(array $data): SubCategory
    {
        return SubCategory::create($data);
    }

    public function update(SubCategory $subCategory, array $data): SubCategory
    {
        $subCategory->update($data);
        return $subCategory->refresh();
    }

    public function delete(SubCategory $subCategory): bool
    {
        return $subCategory->delete();
    }
}