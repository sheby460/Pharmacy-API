<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\DrugBatch;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        private StockMovementService $stockMovementService
    ) {}

    /**
     * Increase available stock and record the movement atomically.
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
        $this->assertMovementDirection(
            $movementType,
            increasing: true,
        );

        $this->assertPositiveQuantity($quantity);

        return DB::transaction(function () use (
            $drugBatch,
            $quantity,
            $movementType,
            $user,
            $referenceType,
            $referenceId,
            $notes
        ) {
            $lockedBatch = DrugBatch::query()
                ->lockForUpdate()
                ->findOrFail($drugBatch->id);

            $before = (int) $lockedBatch->quantity_available;
            if ($quantity > PHP_INT_MAX - $before) {
                throw ValidationException::withMessages([
                    'quantity' => [
                        'The resulting stock quantity exceeds the supported range.',
                    ],
                ]);
            }

            $after = $before + $quantity;

            $lockedBatch->update([
                'quantity_available' => $after,
            ]);

            return $this->stockMovementService->record(
                drugBatch: $lockedBatch,
                movementType: $movementType,
                quantity: $quantity,
                quantityBefore: $before,
                quantityAfter: $after,
                user: $user,
                referenceType: $referenceType,
                referenceId: $referenceId,
                notes: $notes,
            );
        }, 3);
    }

    /**
     * Decrease available stock and record the movement atomically.
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
        $this->assertMovementDirection(
            $movementType,
            increasing: false,
        );

        $this->assertPositiveQuantity($quantity);

        return DB::transaction(function () use (
            $drugBatch,
            $quantity,
            $movementType,
            $user,
            $referenceType,
            $referenceId,
            $notes
        ) {
            $lockedBatch = DrugBatch::query()
                ->lockForUpdate()
                ->findOrFail($drugBatch->id);

            $before = (int) $lockedBatch->quantity_available;

            if ($quantity > $before) {
                throw ValidationException::withMessages([
                    'quantity' => [
                        "Insufficient stock. Available stock: {$before}.",
                    ],
                ]);
            }

            $after = $before - $quantity;

            $lockedBatch->update([
                'quantity_available' => $after,
            ]);

            return $this->stockMovementService->record(
                drugBatch: $lockedBatch,
                movementType: $movementType,
                quantity: $quantity,
                quantityBefore: $before,
                quantityAfter: $after,
                user: $user,
                referenceType: $referenceType,
                referenceId: $referenceId,
                notes: $notes,
            );
        }, 3);
    }

    /**
     * Validate that a movement type matches the stock direction.
     */
    private function assertMovementDirection(
        StockMovementType $movementType,
        bool $increasing,
    ): void {
        $increasingTypes = [
            StockMovementType::PURCHASE,
            StockMovementType::SALE_RETURN,
            StockMovementType::SALE_CANCELLATION,
            StockMovementType::ADJUSTMENT_IN,
        ];

        $decreasingTypes = [
            StockMovementType::SALE,
            StockMovementType::PURCHASE_RETURN,
            StockMovementType::PURCHASE_CANCELLATION,
            StockMovementType::ADJUSTMENT_OUT,
            StockMovementType::DAMAGE,
            StockMovementType::EXPIRED,
        ];

        $allowedTypes = $increasing
            ? $increasingTypes
            : $decreasingTypes;

        if (! in_array($movementType, $allowedTypes, true)) {
            throw ValidationException::withMessages([
                'movement_type' => [
                    sprintf(
                        'Movement type %s cannot %s available stock.',
                        $movementType->value,
                        $increasing ? 'increase' : 'decrease',
                    ),
                ],
            ]);
        }
    }

    /**
     * Validate that stock quantity is a positive whole number.
     */
    private function assertPositiveQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => [
                    'Stock quantity must be greater than zero.',
                ],
            ]);
        }
    }
}
