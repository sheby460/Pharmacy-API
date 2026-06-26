<?php
namespace App\Services\Supplier;

use App\Models\Supplier;
use App\Repositories\Supplier\SupplierRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplierService {
    public function __construct(
        protected SupplierRepositoryInterface $supplierRepository
    )
    {
        
    }

    public function getAllSuppliers(): Collection {
        return $this->supplierRepository->getAll();
    }

    public function paginateSuppliers(int $perPage = 15): LengthAwarePaginator {
        return $this->supplierRepository->paginate($perPage);
    }

    public function getSupplierById(int $id): Supplier {
        return $this->supplierRepository->findById($id);
    }

    public function createSupplier(array $data): Supplier {
        return DB::transaction(function () use ($data) {
            return $this->supplierRepository->create($data);
        });
    }

    public function updateSupplier(int $id, array $data): Supplier {
        return DB::transaction(function () use ($id, $data) {
            $supplier = $this->supplierRepository->findById($id);
            
            return $this->supplierRepository->update($supplier, $data);
        });
    }

      public function deleteSupplier(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $supplier = $this->supplierRepository->findById($id);

            return $this->supplierRepository->delete($supplier);
        });
    }
}