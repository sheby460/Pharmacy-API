<?php

namespace App\Repositories\DrugBatches;

use App\Models\DrugBatch;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DrugBatchRepository implements DrugBatchRepositoryInterface
{
    public function getAll(): Collection
    {
        return DrugBatch::with([
            'drug',
            'supplier',
        ])
            ->latest()
            ->get();
    }

    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?int $drugId = null
    ): LengthAwarePaginator {
        return DrugBatch::query()
            ->with([
                'drug',
                'supplier',
            ])
            ->when($drugId, function ($query) use ($drugId) {
                $query->where(
                    'drug_id',
                    $drugId
                );
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where(
                        'batch_number',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'drug',
                        function ($drug) use ($search) {
                            $drug->where(
                                'drug_name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'drug_code',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): DrugBatch
    {
        return DrugBatch::with([
            'drug',
            'supplier',
            'stockMovements',
        ])->findOrFail($id);
    }

    public function getByDrug(
        int $drugId,
        int $perPage = 15
    ): LengthAwarePaginator {
        return DrugBatch::with([
            'drug',
            'supplier',
        ])
            ->where('drug_id', $drugId)
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): DrugBatch
    {
        return DrugBatch::create($data);
    }

    public function getSellableBatches(
        int $drugId
    ): Collection {
        return DrugBatch::query()
            ->where('drug_id', $drugId)
            ->where(
                'quantity_available',
                '>',
                0
            )
            ->whereDate(
                'expiry_date',
                '>',
                now()->toDateString()
            )
            ->orderBy('expiry_date')
            ->orderBy('id')
            ->get();
    }
}