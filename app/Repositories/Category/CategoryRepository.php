<?php
 namespace App\Repositories\Category;
 use Illuminate\Pagination\LengthAwarePaginator;                            
 use Illuminate\Support\Collection;
 use App\Models\Category;

 
 class CategoryRepository implements CategoryRepositoryInterface
 {
     public function getAll(): Collection
     {
         return Category::query()->latest()->get();
     }
 
     public function paginate(int $perPage = 15): LengthAwarePaginator
     {
         return Category::query()->latest()->paginate($perPage);
     }
 
     public function findById(int $id): Category
     {
         return Category::query()->findOrFail($id);
     }
 
     public function create(array $data)
     {
         return Category::create($data);
     }
 
     public function update(Category $category, array $data): Category
     {
         $category->update($data);
         return $category->refresh();
     }
 
     public function delete(Category $category): bool
     {
         return $category->delete();
     }
 }
 