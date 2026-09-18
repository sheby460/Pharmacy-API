<?php

namespace App\Repositories\SubCategoryRepository;

use App\Models\SubCategory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SubCategoryRepository implements SubCategoryInterface
{
    public function getAll(): Collection
    {
        return SubCategory::query()
            ->with('category')
            ->latest()
            ->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return SubCategory::query()
            ->with('category')
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): SubCategory
    {
        return SubCategory::query()
            ->with('category')
            ->findOrFail($id);
    }

    public function create(array $data): SubCategory
    {
        return SubCategory::create($data);
    }

    public function update(
        SubCategory $subCategory,
        array $data
    ): SubCategory {
        $subCategory->update($data);

        return $subCategory->refresh()->load('category');
    }

    public function delete(SubCategory $subCategory): bool
    {
        return $subCategory->delete();
    }
}