<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\DrugBatch;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    /**
     * Record the inventory audit trail.
     *
     * Stock updates must be performed by InventoryService in the same
     * database transaction as this record.
     */
    public function record(
        DrugBatch $drugBatch,
        StockMovementType $movementType,
        int $quantity,
        int $quantityBefore,
        int $quantityAfter,
        ?User $user = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
    ): StockMovement {
        $this->validateMovement(
            movementType: $movementType,
            quantity: $quantity,
            quantityBefore: $quantityBefore,
            quantityAfter: $quantityAfter,
        );

        return StockMovement::query()->create([
            'drug_id' => $drugBatch->drug_id,
            'drug_batch_id' => $drugBatch->id,
            'created_by' => $user?->id,
            'movement_type' => $movementType->value,
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
        ]);
    }

    private function validateMovement(
        StockMovementType $movementType,
        int $quantity,
        int $quantityBefore,
        int $quantityAfter,
    ): void {
        if ($quantity <= 0) {
            $this->fail(
                'quantity',
                'Movement quantity must be greater than zero.',
            );
        }

        if ($quantityBefore < 0 || $quantityAfter < 0) {
            $this->fail(
                'quantity',
                'Stock quantities cannot be negative.',
            );
        }

        if (abs($quantityAfter - $quantityBefore) !== $quantity) {
            $this->fail(
                'quantity',
                'Movement quantity must match the difference between before and after stock.',
            );
        }

        if ($quantityBefore === $quantityAfter) {
            $this->fail(
                'quantity',
                'A stock movement must change the available quantity.',
            );
        }

        if (
            $this->isIncreasingMovement($movementType)
            && $quantityAfter <= $quantityBefore
        ) {
            $this->fail(
                'movement_type',
                "Movement type {$movementType->value} must increase stock.",
            );
        }

        if (
            $this->isDecreasingMovement($movementType)
            && $quantityAfter >= $quantityBefore
        ) {
            $this->fail(
                'movement_type',
                "Movement type {$movementType->value} must decrease stock.",
            );
        }

        if (
            ! $this->isIncreasingMovement($movementType)
            && ! $this->isDecreasingMovement($movementType)
        ) {
            $this->fail(
                'movement_type',
                "Movement type {$movementType->value} has no configured stock direction.",
            );
        }
    }

    private function isIncreasingMovement(
        StockMovementType $movementType,
    ): bool {
        return in_array($movementType, [
            StockMovementType::PURCHASE,
            StockMovementType::SALE_RETURN,
            StockMovementType::SALE_CANCELLATION,
            StockMovementType::ADJUSTMENT_IN,
        ], true);
    }

    private function isDecreasingMovement(
        StockMovementType $movementType,
    ): bool {
        return in_array($movementType, [
            StockMovementType::SALE,
            StockMovementType::PURCHASE_RETURN,
            StockMovementType::PURCHASE_CANCELLATION,
            StockMovementType::ADJUSTMENT_OUT,
            StockMovementType::DAMAGE,
            StockMovementType::EXPIRED,
        ], true);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => [$message],
        ]);
    }
}
