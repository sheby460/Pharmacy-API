<?php
namespace App\Repositories\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Models\Category;

interface CategoryRepositoryInterface
{
    public function getAll(): Collection;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): Category;

    public function create(array $data);

    public function update(Category $category, array $data);

    public function delete(Category $category);
}