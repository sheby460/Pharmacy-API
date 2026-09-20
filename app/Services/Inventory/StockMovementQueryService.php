<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\DrugBatch;
use App\Models\User;
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
        int $drugBatchId,
        StockMovementType $movementType,
        int $quantity,
        ?User $user = null,
        ?string $notes = null,
     ) {
        $allowedMovementTypes = [
            StockMovementType::ADJUSTMENT_IN,
            StockMovementType::ADJUSTMENT_OUT,
            StockMovementType::DAMAGE,
            StockMovementType::EXPIRED,
        ];

        if (!in_array($movementType, $allowedMovementTypes, true)) {
            throw ValidationException::withMessages([
                'movement_type' => [
                    'The selected movement type is not allowed for stock adjustment.',
                ],
            ]);
        }

        $drugBatch = DrugBatch::query()
            ->findOrFail($drugBatchId);

        /**
         * ADJUSTMENT_IN increases stock.
         */
        if ($movementType === StockMovementType::ADJUSTMENT_IN) {
            return $this->inventoryService->increase(
                drugBatch: $drugBatch,
                quantity: $quantity,
                movementType: $movementType,
                user: $user,
                notes: $notes,
            );
        }

        /**
         * ADJUSTMENT_OUT, DAMAGE and EXPIRED
         * decrease stock.
         */
        return $this->inventoryService->decrease(
            drugBatch: $drugBatch,
            quantity: $quantity,
            movementType: $movementType,
            user: $user,
            notes: $notes,
        );
    }
}
