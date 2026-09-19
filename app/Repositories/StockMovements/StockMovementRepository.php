<?php

namespace App\Repositories\StockMovements;

use App\Models\StockMovement;
use Illuminate\Pagination\LengthAwarePaginator;

class StockMovementRepository
    implements StockMovementRepositoryInterface
{
    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?string $type = null,
        ?int $drugId = null
    ): LengthAwarePaginator {
        return StockMovement::query()
            ->with([
                'drug',
                'drugBatch',
                'createdBy',
            ])
            ->when(
                $type,
                fn ($query) =>
                    $query->where(
                        'movement_type',
                        $type
                    )
            )
            ->when(
                $drugId,
                fn ($query) =>
                    $query->where(
                        'drug_id',
                        $drugId
                    )
            )
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where(
                        'notes',
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
                    )
                    ->orWhereHas(
                        'drugBatch',
                        function ($batch) use ($search) {
                            $batch->where(
                                'batch_number',
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

    public function findById(
        int $id
    ): StockMovement {
        return StockMovement::query()
            ->with([
                'drug',
                'drugBatch',
                'createdBy',
            ])
            ->findOrFail($id);
    }
}