<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\DrugBatch;
use App\Models\StockMovement;
use App\Models\User;

class StockMovementService
{
    /**
     * Record a stock movement.
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
        return StockMovement::create([
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
}