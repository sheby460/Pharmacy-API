<?php

namespace App\Services\Purchase;

use App\Enums\PurchaseStatus;
use App\Enums\StockMovementType;
use App\Models\DrugBatch;
use App\Models\Purchase;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
        $perPage = min(max($perPage, 1), 100);
        $normalizedSearch = trim((string) $search);

        return Purchase::query()
            ->with([
                'supplier',
                'createdBy',
                'receivedBy',
                'cancelledBy',
            ])
            ->when($normalizedSearch !== '', function ($query) use ($normalizedSearch) {
                $searchValue = "%{$normalizedSearch}%";

                $query->where(function ($innerQuery) use ($searchValue) {
                    $innerQuery
                        ->where('invoice_number', 'like', $searchValue)
                        ->orWhereHas('supplier', function ($supplierQuery) use ($searchValue) {
                            $supplierQuery->where(
                                'supplier_name',
                                'like',
                                $searchValue
                            );
                        });
                });
            })
            ->when(
                $status !== null && $status !== '',
                fn ($query) => $query->where('status', $status)
            )
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * Get one purchase with its related records.
     */
    public function findById(int $id): Purchase
    {
        return Purchase::query()
            ->with($this->purchaseRelations())
            ->findOrFail($id);
    }

    /**
     * Create a purchase draft.
     *
     * The authenticated user is required for audit tracking.
     */
    public function create(array $data, User $user): Purchase
    {
        return DB::transaction(function () use ($data, $user) {
            $subtotal = 0.0;
            $items = $data['items'];

            foreach ($items as $item) {
                $quantity = (int) $item['quantity_received'];
                $price = round((float) $item['purchase_price'], 2);

                $subtotal += round($price * $quantity, 2);
            }

            $subtotal = round($subtotal, 2);
            $discount = round((float) ($data['discount'] ?? 0), 2);
            $tax = round((float) ($data['tax'] ?? 0), 2);

            if ($discount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount' => [
                        'The discount cannot exceed the purchase subtotal.',
                    ],
                ]);
            }

            $total = round($subtotal - $discount + $tax, 2);

            $purchase = Purchase::query()->create([
                'supplier_id' => $data['supplier_id'],
                'created_by' => $user->id,
                'invoice_number' => $data['invoice_number'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'status' => PurchaseStatus::DRAFT,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $quantity = (int) $item['quantity_received'];
                $price = round((float) $item['purchase_price'], 2);

                $purchase->items()->create([
                    'drug_id' => $item['drug_id'],
                    'batch_number' => trim($item['batch_number']),
                    'expiry_date' => $item['expiry_date'],
                    'purchase_price' => $price,
                    'selling_price' => round(
                        (float) $item['selling_price'],
                        2
                    ),
                    'quantity_received' => $quantity,
                    'line_total' => round($price * $quantity, 2),
                ]);
            }

            return $purchase->load($this->purchaseRelations());
        }, 3);
    }

    /**
     * Receive a draft purchase and post its stock into inventory.
     */
    public function receive(int $purchaseId, User $user): Purchase
    {
        return DB::transaction(function () use ($purchaseId, $user) {
            $purchase = Purchase::query()
                ->lockForUpdate()
                ->findOrFail($purchaseId);

            if ($purchase->status !== PurchaseStatus::DRAFT) {
                throw ValidationException::withMessages([
                    'purchase' => [
                        'Only draft purchases can be received.',
                    ],
                ]);
            }

            $items = $purchase->items()->orderBy('id')->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'purchase' => [
                        'Cannot receive a purchase without items.',
                    ],
                ]);
            }

            foreach ($items as $item) {
                $drugBatch = DrugBatch::query()
                    ->where('drug_id', $item->drug_id)
                    ->where('batch_number', $item->batch_number)
                    ->lockForUpdate()
                    ->first();

                if ($drugBatch) {
                    if (
                        $drugBatch->expiry_date->toDateString()
                        !== $item->expiry_date->toDateString()
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
                        'supplier_id' => $purchase->supplier_id,
                        'batch_number' => $item->batch_number,
                        'expiry_date' => $item->expiry_date,
                        'purchase_price' => $item->purchase_price,
                        'selling_price' => $item->selling_price,
                        'quantity_received' => 0,
                        'quantity_available' => 0,
                        'received_at' => now(),
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

                $drugBatch->increment('quantity_received', $quantity);

                $this->inventoryService->increase(
                    drugBatch: $drugBatch,
                    quantity: $quantity,
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
                'received_by' => $user->id,
                'received_at' => now(),
            ]);

            return $purchase->fresh($this->purchaseRelations());
        }, 3);
    }

    /**
     * Cancel a draft or reverse a received purchase.
     */
    public function cancel(int $purchaseId, User $user): Purchase
    {
        return DB::transaction(function () use ($purchaseId, $user) {
            $purchase = Purchase::query()
                ->lockForUpdate()
                ->findOrFail($purchaseId);

            if ($purchase->status === PurchaseStatus::CANCELLED) {
                throw ValidationException::withMessages([
                    'purchase' => [
                        'This purchase has already been cancelled.',
                    ],
                ]);
            }

            if ($purchase->status === PurchaseStatus::DRAFT) {
                $purchase->update([
                    'status' => PurchaseStatus::CANCELLED,
                    'cancelled_by' => $user->id,
                    'cancelled_at' => now(),
                ]);

                return $purchase->fresh($this->purchaseRelations());
            }

            if ($purchase->status !== PurchaseStatus::RECEIVED) {
                throw ValidationException::withMessages([
                    'purchase' => [
                        'Only draft or received purchases can be cancelled.',
                    ],
                ]);
            }

            $items = $purchase->items()->orderBy('id')->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'purchase' => [
                        'Cannot cancel a purchase without items.',
                    ],
                ]);
            }

            /*
             * Preflight every item before changing stock.
             * The transaction still guarantees rollback if any
             * subsequent inventory operation fails.
             */
            $reversals = [];

            foreach ($items as $item) {
                $quantity = (int) $item->quantity_received;

                if (!$item->drug_batch_id || $quantity <= 0) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Purchase item {$item->id} has an invalid batch or quantity.",
                        ],
                    ]);
                }

                $drugBatch = DrugBatch::query()
                    ->lockForUpdate()
                    ->findOrFail($item->drug_batch_id);

                if ($quantity > (int) $drugBatch->quantity_available) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Batch {$drugBatch->batch_number} does not have enough available stock to reverse this purchase.",
                        ],
                    ]);
                }

                if ($quantity > (int) $drugBatch->quantity_received) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Batch {$drugBatch->batch_number} has insufficient recorded received quantity.",
                        ],
                    ]);
                }

                $reversals[] = [
                    'item' => $item,
                    'batch' => $drugBatch,
                    'quantity' => $quantity,
                ];
            }

            foreach ($reversals as $reversal) {
                /** @var DrugBatch $drugBatch */
                $drugBatch = $reversal['batch'];
                $quantity = $reversal['quantity'];

                $this->inventoryService->decrease(
                    drugBatch: $drugBatch,
                    quantity: $quantity,
                    movementType: StockMovementType::PURCHASE_CANCELLATION,
                    user: $user,
                    referenceType: Purchase::class,
                    referenceId: $purchase->id,
                    notes: "Cancellation of purchase #{$purchase->id}.",
                );

                $drugBatch->decrement('quantity_received', $quantity);
            }

            $purchase->update([
                'status' => PurchaseStatus::CANCELLED,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
            ]);

            return $purchase->fresh($this->purchaseRelations());
        }, 3);
    }

    /**
     * Relationships needed in purchase API responses.
     */
    private function purchaseRelations(): array
    {
        return [
            'supplier',
            'createdBy',
            'receivedBy',
            'cancelledBy',
            'items.drug',
            'items.drugBatch',
        ];
    }
}