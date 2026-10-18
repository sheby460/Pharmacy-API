<?php

namespace App\Services\Sales;

use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleCancellationService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {
    }

    /**
     * Cancel a completed sale and restore its stock.
     *
     * All stock changes, stock movement records, and the sale status
     * update are committed or rolled back together.
     */
    public function cancel(
        int $saleId,
        User $user,
        string $reason,
    ): Sale {
        $reason = trim($reason);

        $this->validateSaleId($saleId);
        $this->validateCancellationReason($reason);

        return DB::transaction(function () use (
            $saleId,
            $user,
            $reason,
        ): Sale {
            $sale = $this->findSaleForCancellation($saleId);

            $this->validateCancellationEligibility(
                sale: $sale,
                user: $user,
            );

            $items = $this->loadSaleItemsForCancellation($sale);

            $this->restoreInventory(
                sale: $sale,
                items: $items,
                user: $user,
                reason: $reason,
            );

            $this->markSaleAsCancelled(
                sale: $sale,
                user: $user,
                reason: $reason,
            );

            return $this->loadCancelledSale($sale);
        }, 3);
    }

    private function validateSaleId(int $saleId): void
    {
        if ($saleId <= 0) {
            throw ValidationException::withMessages([
                'sale' => [
                    'The sale ID must be a valid positive integer.',
                ],
            ]);
        }
    }

    private function findSaleForCancellation(int $saleId): Sale
    {
        return Sale::query()
            ->lockForUpdate()
            ->findOrFail($saleId);
    }

    private function validateCancellationEligibility(
        Sale $sale,
        User $user,
    ): void {
        if ($sale->status === SaleStatus::CANCELLED) {
            throw ValidationException::withMessages([
                'sale' => ['This sale has already been cancelled.'],
            ]);
        }

        if ($sale->status !== SaleStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'sale' => ['Only completed sales can be cancelled.'],
            ]);
        }

        if (! $user->can('cancel', $sale)) {
            throw new AuthorizationException(
                'You are not authorized to cancel this sale.',
            );
        }
    }

    private function validateCancellationReason(string $reason): void
    {
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => ['A cancellation reason is required.'],
            ]);
        }

        if (mb_strlen($reason) < 3) {
            throw ValidationException::withMessages([
                'reason' => [
                    'The cancellation reason must contain at least 3 characters.',
                ],
            ]);
        }

        if (mb_strlen($reason) > 1000) {
            throw ValidationException::withMessages([
                'reason' => [
                    'The cancellation reason cannot exceed 1000 characters.',
                ],
            ]);
        }
    }

    /**
     * Lock sale items in a stable order before locking their batches.
     *
     * @return EloquentCollection<int, SaleItem>
     */
    private function loadSaleItemsForCancellation(
        Sale $sale,
    ): EloquentCollection {
        $items = SaleItem::query()
            ->where('sale_id', $sale->id)
            ->orderBy('drug_batch_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'sale' => [
                    'The sale has no items and cannot be cancelled.',
                ],
            ]);
        }

        return $items;
    }

    /**
     * Restore the exact quantities sold and record the reason.
     *
     * @param EloquentCollection<int, SaleItem> $items
     */
    private function restoreInventory(
        Sale $sale,
        EloquentCollection $items,
        User $user,
        string $reason,
    ): void {
        foreach ($items as $item) {
            if ((int) $item->quantity <= 0) {
                throw ValidationException::withMessages([
                    'items' => [
                        sprintf(
                            'Sale item %d has an invalid quantity.',
                            $item->id,
                        ),
                    ],
                ]);
            }

            $drugBatch = $item->drugBatch()
                ->lockForUpdate()
                ->first();

            if ($drugBatch === null) {
                throw ValidationException::withMessages([
                    'items' => [
                        sprintf(
                            'The batch for sale item %d was not found.',
                            $item->id,
                        ),
                    ],
                ]);
            }

            $this->inventoryService->increase(
                drugBatch: $drugBatch,
                quantity: (int) $item->quantity,
                movementType: StockMovementType::SALE_CANCELLATION,
                user: $user,
                referenceType: Sale::class,
                referenceId: $sale->id,
                notes: sprintf(
                    'Stock restored for cancelled invoice %s. Reason: %s',
                    $sale->invoice_number,
                    $reason,
                ),
            );
        }
    }

    private function markSaleAsCancelled(
        Sale $sale,
        User $user,
        string $reason,
    ): void {
        $sale->update([
            'status' => SaleStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
            'cancellation_reason' => $reason,
        ]);
    }

    private function loadCancelledSale(Sale $sale): Sale
    {
        return $sale->refresh()->load([
            'customer',
            'createdBy',
            'cancelledBy',
            'items.drug',
            'items.drugBatch',
            'payments',
        ]);
    }
}
