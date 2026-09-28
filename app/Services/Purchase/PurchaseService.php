<?php

namespace App\Services\Purchase;

use App\Enums\PurchaseStatus;
use App\Enums\StockMovementType;
use App\Models\DrugBatch;
use App\Models\Purchase;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Get paginated purchases.
     */
    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?string $status = null
    ): LengthAwarePaginator {
        $normalizedSearch = trim((string) $search);

        return Purchase::query()
            ->with([
                'supplier',
                'createdBy',
            ])
            ->when(
                $normalizedSearch !== '',
                function ($query) use ($normalizedSearch) {
                    $query->where(function ($innerQuery) use (
                        $normalizedSearch
                    ) {
                        $searchValue = "%{$normalizedSearch}%";

                        $innerQuery
                            ->where(
                                'invoice_number',
                                'like',
                                $searchValue
                            )
                            ->orWhereHas(
                                'supplier',
                                function ($supplierQuery) use (
                                    $searchValue
                                ) {
                                    $supplierQuery->where(
                                        'supplier_name',
                                        'like',
                                        $searchValue
                                    );
                                }
                            );
                    });
                }
            )
            ->when(
                $status !== null && $status !== '',
                function ($query) use ($status) {
                    $query->where('status', $status);
                }
            )
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get a single purchase.
     */
    public function findById(int $id): Purchase
    {
        return Purchase::query()
            ->with([
                'supplier',
                'createdBy',
                'items.drug',
                'items.drugBatch',
            ])
            ->findOrFail($id);
    }

    /**
     * Create a purchase draft.
     */
    public function create(
        array $data,
        ?User $user = null
    ): Purchase {
        return DB::transaction(function () use ($data, $user) {
            $subtotal = 0;

            foreach ($data['items'] as $item) {
                $lineTotal =
                    (float) $item['purchase_price']
                    * (int) $item['quantity_received'];

                $subtotal += $lineTotal;
            }

            $discount = (float) ($data['discount'] ?? 0);
            $tax = (float) ($data['tax'] ?? 0);

            $total = max(
                0,
                $subtotal - $discount + $tax
            );

            $purchase = Purchase::query()->create([
                'supplier_id' => $data['supplier_id'],
                'created_by' => $user?->id,
                'invoice_number' =>
                $data['invoice_number'] ?? null,
                'purchase_date' =>
                $data['purchase_date'],
                'status' =>
                PurchaseStatus::DRAFT,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $lineTotal =
                    (float) $item['purchase_price']
                    * (int) $item['quantity_received'];

                $purchase->items()->create([
                    'drug_id' => $item['drug_id'],
                    'batch_number' =>
                    $item['batch_number'],
                    'expiry_date' =>
                    $item['expiry_date'],
                    'purchase_price' =>
                    $item['purchase_price'],
                    'selling_price' =>
                    $item['selling_price'],
                    'quantity_received' =>
                    $item['quantity_received'],
                    'line_total' => $lineTotal,
                ]);
            }

            return $purchase->load([
                'supplier',
                'createdBy',
                'items.drug',
            ]);
        });
    }

    /**
     * Receive a purchase and update inventory.
     */
    public function receive(
        int $purchaseId,
        ?User $user = null
    ): Purchase {
        return DB::transaction(function () use (
            $purchaseId,
            $user
        ) {
            $purchase = Purchase::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($purchaseId);

            if ($purchase->status !== PurchaseStatus::DRAFT) {
                throw ValidationException::withMessages([
                    'purchase' => [
                        'Only draft purchases can be received.',
                    ],
                ]);
            }

            if ($purchase->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'purchase' => [
                        'Cannot receive a purchase without items.',
                    ],
                ]);
            }

            foreach ($purchase->items as $item) {
                $drugBatch = DrugBatch::query()
                    ->where('drug_id', $item->drug_id)
                    ->where('batch_number', $item->batch_number)
                    ->lockForUpdate()
                    ->first();

                if ($drugBatch) {
                    if (
                        $drugBatch->expiry_date
                        ->toDateString()
                        !== $item->expiry_date
                        ->toDateString()
                    ) {
                        throw ValidationException::withMessages([
                            'items' => [
                                "Batch {$item->batch_number} has a different expiry date.",
                            ],
                        ]);
                    }
                } else {
                    $drugBatch = DrugBatch::query()->create([
                        'drug_id' => $item->drug_id,
                        'supplier_id' =>
                        $purchase->supplier_id,
                        'batch_number' =>
                        $item->batch_number,
                        'expiry_date' =>
                        $item->expiry_date,
                        'purchase_price' =>
                        $item->purchase_price,
                        'selling_price' =>
                        $item->selling_price,
                        'quantity_received' => 0,
                        'quantity_available' => 0,
                        'received_at' => now(),
                    ]);
                }

                $drugBatch->update([
                    'quantity_received' =>
                    $drugBatch->quantity_received
                        + $item->quantity_received,
                ]);

                $this->inventoryService->increase(
                    drugBatch: $drugBatch,
                    quantity: $item->quantity_received,
                    movementType: StockMovementType::PURCHASE,
                    user: $user,
                    referenceType: Purchase::class,
                    referenceId: $purchase->id,
                    notes: $purchase->notes,
                );

                $item->update([
                    'drug_batch_id' => $drugBatch->id,
                ]);
            }

            $purchase->update([
                'status' => PurchaseStatus::RECEIVED,
            ]);

            return $purchase->fresh([
                'supplier',
                'createdBy',
                'items.drug',
                'items.drugBatch',
            ]);
        });
    }

    /**
     * Cancel a purchase and reverse received inventory.
     */
    public function cancel(
        int $purchaseId,
        ?User $user = null
    ): Purchase {
        return DB::transaction(function () use (
            $purchaseId,
            $user
        ) {
            $purchase = Purchase::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($purchaseId);

            /*
         * Prevent duplicate cancellation.
         */
            if (
                $purchase->status === PurchaseStatus::CANCELLED
            ) {
                throw ValidationException::withMessages([
                    'purchase' => [
                        'This purchase has already been cancelled.',
                    ],
                ]);
            }

            /*
         * Cancel a draft without changing inventory.
         */
            if (
                $purchase->status === PurchaseStatus::DRAFT
            ) {
                $purchase->update([
                    'status' => PurchaseStatus::CANCELLED,
                ]);

                return $purchase->fresh([
                    'supplier',
                    'createdBy',
                    'items.drug',
                    'items.drugBatch',
                ]);
            }

            /*
         * Only received purchases require inventory reversal.
         */
            if (
                $purchase->status !== PurchaseStatus::RECEIVED
            ) {
                throw ValidationException::withMessages([
                    'purchase' => [
                        'Only draft or received purchases can be cancelled.',
                    ],
                ]);
            }

            if ($purchase->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'purchase' => [
                        'Cannot cancel a purchase without items.',
                    ],
                ]);
            }

            foreach ($purchase->items as $item) {
                if (!$item->drug_batch_id) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Purchase item {$item->id} has no linked drug batch.",
                        ],
                    ]);
                }

                $quantity = (int) $item->quantity_received;

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Purchase item {$item->id} has an invalid quantity.",
                        ],
                    ]);
                }

                /*
             * Lock the batch before checking and updating
             * its received quantity.
             */
                $drugBatch = DrugBatch::query()
                    ->lockForUpdate()
                    ->findOrFail($item->drug_batch_id);

                $availableQuantity = (int) $drugBatch->quantity_available;

                $receivedQuantity = (int) $drugBatch->quantity_received;

                /*
             * Do not reverse stock that is no longer available.
             */
                if ($quantity > $availableQuantity) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Cannot cancel purchase because batch "
                                . "{$drugBatch->batch_number} has only "
                                . "{$availableQuantity} units available, "
                                . "but {$quantity} units must be reversed.",
                        ],
                    ]);
                }

                /*
             * Protect the aggregate received quantity.
             */
                if ($quantity > $receivedQuantity) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Cannot reverse {$quantity} units from batch "
                                . "{$drugBatch->batch_number} because its "
                                . "received quantity is {$receivedQuantity}.",
                        ],
                    ]);
                }

                /*
             * Decrease available inventory and record
             * a reversal stock movement.
             */
                $this->inventoryService->decrease(
                    drugBatch: $drugBatch,
                    quantity: $quantity,
                    movementType: StockMovementType::PURCHASE_CANCELLATION,
                    user: $user,
                    referenceType: Purchase::class,
                    referenceId: $purchase->id,
                    notes: "Cancellation of purchase #{$purchase->id}.",
                );

                /*
             * Decrease the aggregate quantity received
             * after the inventory reversal succeeds.
             */
                $drugBatch->update([
                    'quantity_received' => $receivedQuantity - $quantity,
                ]);
            }

            /*
         * Mark the purchase as cancelled only after
         * every inventory reversal succeeds.
         */
            $purchase->update([
                'status' => PurchaseStatus::CANCELLED,
            ]);

            return $purchase->fresh([
                'supplier',
                'createdBy',
                'items.drug',
                'items.drugBatch',
            ]);
        });
    }
}
