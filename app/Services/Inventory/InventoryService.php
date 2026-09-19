<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\DrugBatch;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    public function __construct(
        private StockMovementService $stockMovementService
    ) {
    }

    /**
     * Increase stock.
     */
    public function increase(
        DrugBatch $drugBatch,
        int $quantity,
        StockMovementType $movementType,
        ?User $user = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new RuntimeException(
                'Stock quantity must be greater than zero.'
            );
        }

        return DB::transaction(function () use (
            $drugBatch,
            $quantity,
            $movementType,
            $user,
            $referenceType,
            $referenceId,
            $notes
        ) {
            $drugBatch = DrugBatch::query()
                ->lockForUpdate()
                ->findOrFail($drugBatch->id);

            $before = $drugBatch->quantity_available;

            $after = $before + $quantity;

            $drugBatch->update([
                'quantity_available' => $after,
            ]);

            return $this->stockMovementService->record(
                drugBatch: $drugBatch,
                movementType: $movementType,
                quantity: $quantity,
                quantityBefore: $before,
                quantityAfter: $after,
                user: $user,
                referenceType: $referenceType,
                referenceId: $referenceId,
                notes: $notes,
            );
        });
    }

    /**
     * Decrease stock.
     */
    public function decrease(
        DrugBatch $drugBatch,
        int $quantity,
        StockMovementType $movementType,
        ?User $user = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new RuntimeException(
                'Stock quantity must be greater than zero.'
            );
        }

        return DB::transaction(function () use (
            $drugBatch,
            $quantity,
            $movementType,
            $user,
            $referenceType,
            $referenceId,
            $notes
        ) {
            $drugBatch = DrugBatch::query()
                ->lockForUpdate()
                ->findOrFail($drugBatch->id);

            $before = $drugBatch->quantity_available;

            if ($quantity > $before) {
                throw new RuntimeException(
                    "Insufficient stock. Available stock: {$before}."
                );
            }

            $after = $before - $quantity;

            $drugBatch->update([
                'quantity_available' => $after,
            ]);

            return $this->stockMovementService->record(
                drugBatch: $drugBatch,
                movementType: $movementType,
                quantity: $quantity,
                quantityBefore: $before,
                quantityAfter: $after,
                user: $user,
                referenceType: $referenceType,
                referenceId: $referenceId,
                notes: $notes,
            );
        });
    }
}