<?php

namespace App\Repositories\Supplier;

 use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

 class SupplierRepository implements SupplierRepositoryInterface 
 {
  public function getAll(): Collection
  {
    return Supplier::query()->latest()->get();
  }

  public function paginate(int $perPage = 15): LengthAwarePaginator
  {
    return Supplier::query()->latest()->paginate($perPage);
  }

  public function findById(int $id): Supplier
  {
    return Supplier::query()->findOrFail($id);
  }

  public function create(array $data)
  {
    return Supplier::create($data);
  }

  public function update(Supplier $supplier, array $data): Supplier
  {
    $supplier->update($data);
    return $supplier->refresh();
  }

  public function delete(Supplier $supplier): bool
  {
    return $supplier->delete();
  }

 }
