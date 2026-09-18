<?php

namespace App\Repositories\Drugs;

use App\Models\Drug;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DrugsRepository implements DrugsRepositoryInterface
{
    public function getAll(): Collection
    {
        return Drug::with(['category', 'subCategory'])
            ->latest()
            ->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Drug::with(['category', 'subCategory'])
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): Drug
    {
        return Drug::with(['category', 'subCategory'])
            ->findOrFail($id);
    }

    public function create(array $data): Drug
    {
        return Drug::create($data);  
    }

    public function update(Drug $drug, array $data): Drug
    {
        $drug->update($data);
        return $drug->fresh(['category', 'subCategory']);
    }

    public function delete(Drug $drug): bool
    {
        return $drug->delete(); 
    }
}