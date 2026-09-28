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
    ) {}

    /**
     * Cancel a completed sale.
     *
     * The sale, stock restoration, and stock movements
     * are processed within a database transaction.
     */
    public function cancel(
        int $saleId,
        User $user,
        string $reason,
    ): Sale {
        // Validate basic input before starting the transaction.
        $this->validateSaleId($saleId);

        $this->validateCancellationReason($reason);

        return DB::transaction(function () use (
            $saleId,
            $user,
            $reason,
        ): Sale {
            // Find and lock the sale.
            $sale = $this->findSaleForCancellation($saleId);

            // Check whether the current user can cancel the sale.
            $this->validateCancellationEligibility(
                sale: $sale,
                user: $user,
            );

            // Load and lock the sale items.
            $items = $this->loadSaleItemsForCancellation($sale);

            // Restore stock for all sale items.
            $this->restoreInventory(
                sale: $sale,
                items: $items,
                user: $user,
            );

            // Update the sale cancellation details.
            $this->markSaleAsCancelled(
                sale: $sale,
                user: $user,
                reason: $reason,
            );

            // Return the cancelled sale with its relationships.
            return $this->loadCancelledSale($sale);
        });
    }

    /**
     * Validate the sale ID before processing cancellation.
     */
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

    /**
     * Find and lock the sale for cancellation.
     */
    private function findSaleForCancellation(int $saleId): Sale
    {
        $sale = Sale::query()
            ->lockForUpdate()
            ->find($saleId);

        if (!$sale) {
            throw ValidationException::withMessages([
                'sale' => [
                    'The selected sale was not found.',
                ],
            ]);
        }

        return $sale;
    }

    /**
     * Validate the sale's cancellation eligibility.
     */
    private function validateCancellationEligibility(
        Sale $sale,
        User $user,
    ): void {
        if ($sale->status === SaleStatus::CANCELLED) {
            throw ValidationException::withMessages([
                'sale' => [
                    'This sale has already been cancelled.',
                ],
            ]);
        }

        if ($sale->status !== SaleStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'sale' => [
                    'Only completed sales can be cancelled.',
                ],
            ]);
        }

        if (!$user->can('cancel', $sale)) {
            throw new AuthorizationException(
                'You are not authorized to cancel this sale.',
            );
        }
    }

    /**
     * Validate the cancellation reason.
     */
    private function validateCancellationReason(string $reason): void
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => [
                    'A cancellation reason is required.',
                ],
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
     * Load sale items for cancellation.
     *
     * Sale items are locked to prevent concurrent
     * operations against the same records.
     *
     * @return EloquentCollection<int, SaleItem>
     */
    private function loadSaleItemsForCancellation(
        Sale $sale,
    ): EloquentCollection {
        $items = SaleItem::query()
            ->where('sale_id', $sale->id)
            ->with('drug')
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
     * Restore stock for each sale item.
     *
     * Cancellation is recorded separately from a customer return.
     *
     * @param EloquentCollection<int, SaleItem> $items
     */
    private function restoreInventory(
        Sale $sale,
        EloquentCollection $items,
        User $user,
    ): void {
        foreach ($items as $item) {
            $drugBatch = $item->drugBatch()
                ->lockForUpdate()
                ->first();

            if (!$drugBatch) {
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
                    'Stock restored after cancellation of invoice %s.',
                    $sale->invoice_number,
                ),
            );
        }
    }

    /**
     * Update the sale cancellation details.
     */
    private function markSaleAsCancelled(
        Sale $sale,
        User $user,
        string $reason,
    ): void {
        $sale->update([
            'status' => SaleStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
            'cancellation_reason' => trim($reason),
        ]);
    }

    /**
     * Load the cancelled sale and relationships.
     */
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