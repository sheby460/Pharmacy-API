<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\DrugBatch;
use App\Repositories\StockMovements\StockMovementRepositoryInterface;
use Illuminate\Validation\ValidationException;

class StockMovementQueryService
{
    public function __construct(
        protected StockMovementRepositoryInterface $repository,
        protected InventoryService $inventoryService
    ) {}

    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?string $type = null,
        ?int $drugId = null
    ) {
        return $this->repository->paginate(
            $perPage,
            $search,
            $type,
            $drugId
        );
    }

    public function findById(int $id)
    {
        return $this->repository->findById($id);
    }

    public function adjust(
        int $batchId,
        StockMovementType $type,
        int $quantity,
        $user = null,
        ?string $notes = null
    ) {
        $batch =
            DrugBatch::findOrFail($batchId);

        if (
            !in_array(
                $type,
                [
                    StockMovementType::ADJUSTMENT_IN,
                    StockMovementType::ADJUSTMENT_OUT,
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'movement_type' =>
                    'Only stock adjustment movements are allowed here.',
            ]);
        }

        if (
            $type === StockMovementType::ADJUSTMENT_IN
        ) {
            return $this->inventoryService->increase(
                drugBatch: $batch,
                quantity: $quantity,
                movementType: $type,
                user: $user,
                notes: $notes
            );
        }

        return $this->inventoryService->decrease(
            drugBatch: $batch,
            quantity: $quantity,
            movementType: $type,
            user: $user,
            notes: $notes
        );
    }
}