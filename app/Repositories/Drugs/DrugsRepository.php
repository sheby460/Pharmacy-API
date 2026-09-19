<?php

namespace App\Repositories\Drugs;

use App\Models\Drug;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DrugsRepository implements DrugsRepositoryInterface
{
    public function getAll(): Collection
    {
        return Drug::query()
            ->with([
                'category',
                'subCategory',
            ])
            ->withCount('batches')
            ->withSum(
                'batches as current_stock',
                'quantity_available'
            )
            ->latest()
            ->get();
    }

    public function paginate(
        int $perPage = 15,
        ?string $search = null
    ): LengthAwarePaginator {
        return Drug::query()
            ->with([
                'category',
                'subCategory',
            ])
            ->withCount('batches')
            ->withSum(
                'batches as current_stock',
                'quantity_available'
            )
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('drug_code', 'like', "%{$search}%")
                        ->orWhere('drug_name', 'like', "%{$search}%")
                        ->orWhere('generic_name', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('manufacturer', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): Drug
    {
        return Drug::query()
            ->with([
                'category',
                'subCategory',
            ])
            ->withCount('batches')
            ->withSum(
                'batches as current_stock',
                'quantity_available'
            )
            ->findOrFail($id);
    }

    public function create(array $data): Drug
    {
        $drug = Drug::create($data);

        return $drug->load([
            'category',
            'subCategory',
        ]);
    }

    public function update(
        Drug $drug,
        array $data
    ): Drug {
        $drug->update($data);

        return $drug->fresh([
            'category',
            'subCategory',
        ]);
    }

    public function delete(Drug $drug): bool
    {
        return $drug->delete();
    }
}