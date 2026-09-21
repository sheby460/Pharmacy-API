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
    ) {
    }

    /**
     * Get paginated purchases.
     */
    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?string $status = null
    ): LengthAwarePaginator {
        return Purchase::query()
            ->with([
                'supplier',
                'createdBy',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where(
                            'invoice_number',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                            $supplierQuery->where(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                        });
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
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
}