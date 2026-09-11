<?php
namespace App\Services\SubCategoryService;
use App\Repositories\SubCategoryRepository\SubCategoryInterface;

class SubCategoryService
{

    public function __construct(
        protected SubCategoryInterface $subCategoryRepository
    )
    {

    }

    public function getAll()
    {
        return $this->subCategoryRepository->getAll();
    }

    public function paginate(int $perPage = 15)
    {
        return $this->subCategoryRepository->paginate($perPage);
    }

    public function findById(int $id)
    {
        return $this->subCategoryRepository->findById($id);
    }

    public function create(array $data)
    {
        return $this->subCategoryRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        $subCategory = $this->findById($id);
        return $this->subCategoryRepository->update($subCategory, $data);
    }

    public function delete(int $id)
    {
        $subCategory = $this->findById($id);
        return $this->subCategoryRepository->delete($subCategory);
    }
}