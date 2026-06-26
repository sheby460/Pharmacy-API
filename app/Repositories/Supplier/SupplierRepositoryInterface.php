<?php
namespace App\Repositories\Supplier;

use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SupplierRepositoryInterface
{
    public function getAll(): Collection;

    public function paginate(int $perPage =15): LengthAwarePaginator;

    public function findById(int $id): Supplier;

    public function create(array $data);

    public function update(Supplier $supplier, array $data) : Supplier;

    public function delete(Supplier $supplier): bool;
        
    
}