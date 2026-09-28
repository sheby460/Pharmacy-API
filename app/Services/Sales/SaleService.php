<?php

namespace App\Services\Sales;

use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\DrugBatch;
use App\Models\Sale;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function create(
        array $data,
        User $user
    ): Sale {
        return DB::transaction(function () use ($data, $user): Sale {
            $items = collect($data['items'] ?? []);
            $payments = collect($data['payments'] ?? []);
            $this->validateSaleInput($items, $payments);
            $calculatedItems = $this->prepareSaleItems($items);
            $subtotal = $this->calculateSubtotal($calculatedItems);
            $discount = $this->normalizeMoney(
                $data['discount'] ?? 0
            );
            $tax = $this->normalizeMoney(
                $data['tax'] ?? 0
            );
            $this->validateDiscount($discount, $subtotal);
            $total = $this->calculateTotal(
                $subtotal,
                $discount,
                $tax
            );

            $this->validatePayments(
                $payments,
                $total
            );

            $sale = Sale::query()->create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'customer_id' => $data['customer_id'] ?? null,
                'created_by' => $user->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'status' => SaleStatus::COMPLETED,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->createSaleItems(
                $sale,
                $calculatedItems,
                $user
            );

            $this->createSalePayments(
                $sale,
                $payments
            );

            return $sale->load([
                'customer',
                'createdBy',
                'items.drug',
                'items.drugBatch',
                'payments',
            ]);
        });
    }
    private function validateSaleInput(
        Collection $items,
        Collection $payments,
    ): void {
        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => [
                    'A sale must contain at least one item.',
                ],
            ]);
        }

        if ($payments->isEmpty()) {
            throw ValidationException::withMessages([
                'payments' => [
                    'A sale must contain at least one payment.',
                ],
            ]);
        }

        $this->validateSaleItems($items);
    }

    /**
     * Validate sale item quantities and duplicate batches.
     *
     * @param Collection<int, mixed> $items
     */
    private function validateSaleItems(Collection $items): void
    {
        $batchIds = [];

        foreach ($items as $index => $item) {
            $batchId = (int) ($item['drug_batch_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($batchId <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.drug_batch_id" => [
                        'A valid drug batch is required.',
                    ],
                ]);
            }

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => [
                        'The quantity must be greater than zero.',
                    ],
                ]);
            }

            if (in_array($batchId, $batchIds, true)) {
                throw ValidationException::withMessages([
                    "items.{$index}.drug_batch_id" => [
                        'The same drug batch cannot be added more than once.',
                    ],
                ]);
            }

            $batchIds[] = $batchId;
        }
    }

    /**
     * Resolve batches and prepare server-calculated sale items.
     * Client-provided prices and totals are intentionally ignored.
     */
    private function prepareSaleItems(
        Collection $items
    ): Collection {
        return $items->map(function (array $item): array {
            $batch = DrugBatch::query()
                ->with('drug')
                ->lockForUpdate()
                ->find($item['drug_batch_id']);

            if (!$batch) {
                throw ValidationException::withMessages([
                    'items' => 'One of the selected drug batches was not found.',
                ]);
            }
            $this->validateBatch($batch);
            $quantity = (int) $item['quantity'];

            if ($quantity > $batch->quantity_available) {
                throw ValidationException::withMessages([
                    'items' => sprintf(
                        'Insufficient stock for batch %s. Available quantity: %d.',
                        $batch->batch_number,
                        $batch->quantity_available
                    ),
                ]);
            }

            $unitPrice = $this->resolveSellingPrice($batch);
            $subtotal = $this->roundMoney(
                $unitPrice * $quantity
            );

            return [
                'drug_id' => $batch->drug_id,
                'drug_batch_id' => $batch->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => 0,
                'tax' => 0,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'batch' => $batch,
            ];
        });
    }

    /**
     * Validate the selected batch.
     */
    private function validateBatch(DrugBatch $batch): void
    {
        if (!$batch->drug) {
            throw ValidationException::withMessages([
                'items' => 'A selected batch is not associated with a drug.',
            ]);
        }

        if ($batch->expiry_date->isPast()) {
            throw ValidationException::withMessages([
                'items' => sprintf(
                    'Drug batch %s has expired and cannot be sold.',
                    $batch->batch_number
                ),
            ]);
        }

        if (!$batch->hasAvailableStock()) {
            throw ValidationException::withMessages([
                'items' => sprintf(
                    'Drug batch %s has no available stock.',
                    $batch->batch_number
                ),
            ]);
        }

        if (!$batch->drug->is_active) {
            throw ValidationException::withMessages([
                'items' => sprintf(
                    'Drug %s is inactive and cannot be sold.',
                    $batch->drug->drug_name
                ),
            ]);
        }
    }

    /**
     * Resolve the selling price from the batch.
     *
     * Batch price takes priority because different batches
     * may have different selling prices.
     */
    private function resolveSellingPrice(DrugBatch $batch): float
    {
        $batchPrice = (float) $batch->selling_price;

        if ($batchPrice < 0) {
            throw ValidationException::withMessages([
                'items' => 'The selected batch has an invalid selling price.',
            ]);
        }

        return $this->roundMoney($batchPrice);
    }

    /**
     * Calculate the subtotal for all sale items.
     *
     * @param Collection<int, array<string, mixed>> $items
     */
    private function calculateSubtotal(Collection $items): float
    {
        return $this->roundMoney(
            $items->sum(
                fn(array $item): float => (float) $item['subtotal']
            )
        );
    }

    /**
     * Calculate the final sale total.
     */
    private function calculateTotal(
        float $subtotal,
        float $discount,
        float $tax
    ): float {
        $total = $subtotal - $discount + $tax;

        if ($total < 0) {
            throw ValidationException::withMessages([
                'total' => 'The sale total cannot be negative.',
            ]);
        }

        return $this->roundMoney($total);
    }

    /**
     * Validate the discount.
     */
    private function validateDiscount(
        float $discount,
        float $subtotal
    ): void {
        if ($discount > $subtotal) {
            throw ValidationException::withMessages([
                'discount' =>
                'The discount cannot be greater than the subtotal.',
            ]);
        }
    }

    /**
     * Validate payment totals.
     *
     * This implementation requires exact payment matching.
     * Change handling can be introduced later through a
     * dedicated cash payment policy.
     *
     * @param Collection<int, mixed> $payments
     */
    private function validatePayments(
        Collection $payments,
        float $total
    ): void {
        $paymentTotal = $this->roundMoney(
            $payments->sum(
                fn(array $payment): float =>
                (float) $payment['amount']
            )
        );

        if (abs($paymentTotal - $total) > 0.01) {
            throw ValidationException::withMessages([
                'payments' => sprintf(
                    'Payment total must equal the sale total. Expected: %.2f, received: %.2f.',
                    $total,
                    $paymentTotal
                ),
            ]);
        }
    }

    /**
     * Create sale items and decrease inventory.
     *
     * @param Collection<int, array<string, mixed>> $items
     */
    private function createSaleItems(
        Sale $sale,
        Collection $items,
        User $user
    ): void {
        foreach ($items as $item) {
            /** @var DrugBatch $batch */
            $batch = $item['batch'];

            $this->inventoryService->decrease(
                drugBatch: $batch,
                quantity: $item['quantity'],
                movementType: StockMovementType::SALE,
                user: $user,
                referenceType: Sale::class,
                referenceId: $sale->id,
                notes: sprintf(
                    'Stock deducted for invoice %s.',
                    $sale->invoice_number
                ),
            );

            $sale->items()->create([
                'drug_id' => $item['drug_id'],
                'drug_batch_id' => $item['drug_batch_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount' => $item['discount'],
                'tax' => $item['tax'],
                'subtotal' => $item['subtotal'],
                'total' => $item['total'],
            ]);
        }
    }

    /**
     * Create payment records.
     *
     * @param Collection<int, mixed> $payments
     */
    private function createSalePayments(
        Sale $sale,
        Collection $payments
    ): void {
        foreach ($payments as $payment) {
            $sale->payments()->create([
                'payment_method' => $payment['payment_method'],
                'amount' => $this->normalizeMoney($payment['amount']),
                'reference' => $payment['reference'] ?? null,
                'notes' => $payment['notes'] ?? null,
            ]);
        }
    }

    /**
     * Generate a unique invoice number.
     */
    private function generateInvoiceNumber(): string
    {
        do {
            $invoiceNumber = sprintf(
                'INV-%s-%s',
                now()->format('YmdHis'),
                Str::upper(Str::random(6))
            );
        } while (
            Sale::query()
            ->where('invoice_number', $invoiceNumber)
            ->exists()
        );

        return $invoiceNumber;
    }

    /**
     * Normalize a monetary value to two decimal places.
     */
    private function normalizeMoney(mixed $value): float
    {
        return $this->roundMoney((float) $value);
    }

    /**
     * Round monetary values consistently.
     */
    private function roundMoney(float $value): float
    {
        return round($value, 2);
    }
}
