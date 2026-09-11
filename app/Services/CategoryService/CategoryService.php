<?php

namespace App\Services\CategoryService;
use App\Repositories\Category\CategoryRepositoryInterface;

class CategoryService
{

    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    )
    {

    }

    public function getAll()
    {
        return $this->categoryRepository->getAll();
    }

    public function paginate(int $perPage = 15)
    {
        return $this->categoryRepository->paginate($perPage);
    }

    public function findById(int $id)
    {
        return $this->categoryRepository->findById($id);
    }

    public function create(array $data)
    {
        return $this->categoryRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        $category = $this->findById($id);
        return $this->categoryRepository->update($category, $data);
    }

    public function delete(int $id)
    {
        $category = $this->findById($id);
        return $this->categoryRepository->delete($category);
    }
}