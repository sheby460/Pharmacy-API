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
    /**
     * Maximum supported monetary amount: 9,999,999,999.99.
     */
    private const MAX_MONEY_CENTS = 999999999999;

    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * Create a sale, deduct stock, and record payments atomically.
     *
     * Prices are taken from the database, never from client input.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data, User $user): Sale
    {
        return DB::transaction(function () use ($data, $user): Sale {
            $items = collect($data['items'] ?? []);
            $payments = collect($data['payments'] ?? []);

            $this->validateSaleInput($items, $payments);

            $calculatedItems = $this->prepareSaleItems($items);

            $subtotalCents = 0;

            foreach ($calculatedItems as $item) {
                $lineSubtotal = $item['subtotal_cents'];

                if (
                    $lineSubtotal > self::MAX_MONEY_CENTS - $subtotalCents
                ) {
                    $this->fail(
                        'items',
                        'The sale subtotal exceeds the maximum supported amount.',
                    );
                }

                $subtotalCents += $lineSubtotal;
            }

            $discountCents = $this->moneyToCents(
                $data['discount'] ?? 0,
                'discount',
            );

            $taxCents = $this->moneyToCents(
                $data['tax'] ?? 0,
                'tax',
            );

            if ($discountCents > $subtotalCents) {
                $this->fail(
                    'discount',
                    'The discount cannot exceed the subtotal.',
                );
            }

            $netSubtotalCents = $subtotalCents - $discountCents;

            if ($taxCents > self::MAX_MONEY_CENTS - $netSubtotalCents) {
                $this->fail(
                    'tax',
                    'The sale total exceeds the maximum supported amount.',
                );
            }

            $totalCents = $netSubtotalCents + $taxCents;

            if ($totalCents <= 0) {
                $this->fail(
                    'total',
                    'The sale total must be greater than zero.',
                );
            }

            $this->validatePayments($payments, $totalCents);

            $sale = Sale::query()->create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'customer_id' => $data['customer_id'] ?? null,
                'created_by' => $user->id,
                'subtotal' => $this->centsToMoney($subtotalCents),
                'discount' => $this->centsToMoney($discountCents),
                'tax' => $this->centsToMoney($taxCents),
                'total' => $this->centsToMoney($totalCents),
                'status' => SaleStatus::COMPLETED,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->createSaleItems(
                sale: $sale,
                items: $calculatedItems,
                user: $user,
            );

            $this->createSalePayments(
                sale: $sale,
                payments: $payments,
            );

            return $sale->load([
                'customer',
                'createdBy',
                'items.drug',
                'items.drugBatch',
                'payments',
            ]);
        }, 3);
    }

    /**
     * Validate the basic structure before accessing nested fields.
     *
     * @param Collection<int|string, mixed> $items
     * @param Collection<int|string, mixed> $payments
     */
    private function validateSaleInput(
        Collection $items,
        Collection $payments,
    ): void {
        if ($items->isEmpty()) {
            $this->fail(
                'items',
                'A sale must contain at least one item.',
            );
        }

        if ($payments->isEmpty()) {
            $this->fail(
                'payments',
                'A sale must contain at least one payment.',
            );
        }

        $seenBatchIds = [];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                $this->fail(
                    "items.{$index}",
                    'The sale item is invalid.',
                );
            }

            $batchId = filter_var(
                $item['drug_batch_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]],
            );

            if ($batchId === false) {
                $this->fail(
                    "items.{$index}.drug_batch_id",
                    'A valid drug batch is required.',
                );
            }

            $quantity = filter_var(
                $item['quantity'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]],
            );

            if ($quantity === false) {
                $this->fail(
                    "items.{$index}.quantity",
                    'Quantity must be a positive whole number.',
                );
            }

            if (isset($seenBatchIds[$batchId])) {
                $this->fail(
                    "items.{$index}.drug_batch_id",
                    'Each drug batch must appear only once per sale.',
                );
            }

            $seenBatchIds[$batchId] = true;
        }

        foreach ($payments as $index => $payment) {
            if (! is_array($payment)) {
                $this->fail(
                    "payments.{$index}",
                    'The payment is invalid.',
                );
            }

            $method = $payment['payment_method'] ?? null;

            if (
                ! is_string($method)
                || trim($method) === ''
            ) {
                $this->fail(
                    "payments.{$index}.payment_method",
                    'A payment method is required.',
                );
            }

            $this->moneyToCents(
                $payment['amount'] ?? null,
                "payments.{$index}.amount",
            );
        }
    }

    /**
     * Return batches locked in ascending ID order.
     *
     * @param Collection<int|string, mixed> $items
     * @return Collection<int, array<string, mixed>>
     */
    private function prepareSaleItems(Collection $items): Collection
    {
        $batchIds = $items
            ->pluck('drug_batch_id')
            ->map(fn($id): int => (int) $id)
            ->sort()
            ->values();

        $batches = DrugBatch::query()
            ->with('drug')
            ->whereIn('id', $batchIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $prepared = collect();

        foreach ($items as $index => $item) {
            $batchId = (int) $item['drug_batch_id'];
            $batch = $batches->get($batchId);

            if ($batch === null) {
                $this->fail(
                    "items.{$index}.drug_batch_id",
                    "Drug batch {$batchId} was not found.",
                );
            }

            $this->validateBatch($batch);

            $quantity = (int) $item['quantity'];
            $available = (int) $batch->quantity_available;

            if ($quantity > $available) {
                $this->fail(
                    "items.{$index}.quantity",
                    sprintf(
                        'Insufficient stock for batch %s. Available: %d.',
                        $batch->batch_number,
                        $available,
                    ),
                );
            }

            $unitPriceCents = $this->moneyToCents(
                $batch->selling_price,
                "items.{$index}.unit_price",
            );

            if (
                $unitPriceCents > 0
                && $quantity > intdiv(
                    self::MAX_MONEY_CENTS,
                    $unitPriceCents,
                )
            ) {
                $this->fail(
                    "items.{$index}.quantity",
                    'The item subtotal exceeds the maximum supported amount.',
                );
            }

            $subtotalCents = $unitPriceCents * $quantity;

            $prepared->push([
                'drug_id' => $batch->drug_id,
                'drug_batch_id' => $batch->id,
                'quantity' => $quantity,
                'unit_price' => $this->centsToMoney($unitPriceCents),
                'discount' => '0.00',
                'tax' => '0.00',
                'subtotal' => $this->centsToMoney($subtotalCents),
                'total' => $this->centsToMoney($subtotalCents),
                'subtotal_cents' => $subtotalCents,
                'drug_code_snapshot' => $batch->drug->drug_code,
                'drug_name_snapshot' => $batch->drug->drug_name,
                'batch_number_snapshot' => $batch->batch_number,
                'expiry_date_snapshot' => $batch->expiry_date?->toDateString(),
                'batch' => $batch,
            ]);
        }

        return $prepared;
    }

    private function validateBatch(DrugBatch $batch): void
    {
        if (! $batch->relationLoaded('drug') || $batch->drug === null) {
            $this->fail(
                'items',
                'The drug associated with this batch could not be found.',
            );
        }

        if ($batch->isExpired()) {
            $this->fail(
                'items',
                "Batch {$batch->batch_number} is expired or has no expiry date.",
            );
        }

        if (! $batch->hasAvailableStock()) {
            $this->fail(
                'items',
                "Batch {$batch->batch_number} has no available stock.",
            );
        }

        if (! $batch->drug->is_active) {
            $this->fail(
                'items',
                "The drug associated with batch {$batch->batch_number} is inactive.",
            );
        }
    }

    /**
     * Validate payment amounts and require exact payment of the sale total.
     *
     * @param Collection<int|string, mixed> $payments
     */
    private function validatePayments(
        Collection $payments,
        int $totalCents,
    ): void {
        $paymentCents = 0;

        foreach ($payments as $index => $payment) {
            $amountCents = $this->moneyToCents(
                $payment['amount'] ?? null,
                "payments.{$index}.amount",
            );

            if ($amountCents <= 0) {
                $this->fail(
                    "payments.{$index}.amount",
                    'Payment amount must be greater than zero.',
                );
            }

            if (
                $amountCents > self::MAX_MONEY_CENTS - $paymentCents
            ) {
                $this->fail(
                    'payments',
                    'The payment total exceeds the maximum supported amount.',
                );
            }

            $paymentCents += $amountCents;
        }

        if ($paymentCents !== $totalCents) {
            $this->fail(
                'payments',
                sprintf(
                    'Payment total must equal the sale total. Expected: %s, received: %s.',
                    $this->centsToMoney($totalCents),
                    $this->centsToMoney($paymentCents),
                ),
            );
        }
    }

    /**
     * Deduct inventory and persist each sale item.
     *
     * @param Collection<int, array<string, mixed>> $items
     */
    private function createSaleItems(
        Sale $sale,
        Collection $items,
        User $user,
    ): void {
        foreach ($items as $item) {
            $this->inventoryService->decrease(
                drugBatch: $item['batch'],
                quantity: $item['quantity'],
                movementType: StockMovementType::SALE,
                user: $user,
                referenceType: Sale::class,
                referenceId: $sale->id,
                notes: "Stock deducted for invoice {$sale->invoice_number}.",
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
                'drug_code_snapshot' => $item['drug_code_snapshot'] ?? null,
                'drug_name_snapshot' => $item['drug_name_snapshot'] ?? null,
                'batch_number_snapshot' => $item['batch_number_snapshot'] ?? null,
                'expiry_date_snapshot' => $item['expiry_date_snapshot'] ?? null,
            ]);
        }
    }

    /**
     * @param Collection<int|string, mixed> $payments
     */
    private function createSalePayments(
        Sale $sale,
        Collection $payments,
    ): void {
        foreach ($payments as $index => $payment) {
            $sale->payments()->create([
                'payment_method' => trim($payment['payment_method']),
                'amount' => $this->centsToMoney(
                    $this->moneyToCents(
                        $payment['amount'],
                        "payments.{$index}.amount",
                    ),
                ),
                'reference' => $payment['reference'] ?? null,
                'notes' => $payment['notes'] ?? null,
            ]);
        }
    }

    private function generateInvoiceNumber(): string
    {
        do {
            $invoiceNumber = sprintf(
                'INV-%s-%s',
                now()->format('YmdHis'),
                Str::upper(Str::random(8)),
            );
        } while (
            Sale::query()
            ->where('invoice_number', $invoiceNumber)
            ->exists()
        );

        return $invoiceNumber;
    }

    /**
     * Convert a nonnegative monetary value to integer cents.
     *
     * Rejects amounts with more than two decimal places instead of
     * silently rounding them.
     */
    private function moneyToCents(mixed $value, string $field): int
    {
        if (
            ! is_string($value)
            && ! is_int($value)
            && ! is_float($value)
        ) {
            $this->fail(
                $field,
                'Enter a valid nonnegative monetary amount with at most two decimal places.',
            );
        }

        if (is_float($value) && ! is_finite($value)) {
            $this->fail(
                $field,
                'Enter a valid nonnegative monetary amount.',
            );
        }

        $amount = trim((string) $value);

        if (! preg_match('/\A\d+(?:\.\d{1,2})?\z/', $amount)) {
            $this->fail(
                $field,
                'Enter a valid nonnegative monetary amount with at most two decimal places.',
            );
        }

        [$whole, $fraction] = array_pad(
            explode('.', $amount, 2),
            2,
            '0',
        );

        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad($fraction, 2, '0');

        if (
            strlen($whole) > 10
            || (
                strlen($whole) === 10
                && strcmp($whole, '9999999999') > 0
            )
        ) {
            $this->fail(
                $field,
                'The monetary amount exceeds the maximum supported amount.',
            );
        }

        $cents = ((int) $whole * 100) + (int) $fraction;

        if ($cents > self::MAX_MONEY_CENTS) {
            $this->fail(
                $field,
                'The monetary amount exceeds the maximum supported amount.',
            );
        }

        return $cents;
    }

    private function centsToMoney(int $cents): string
    {
        return sprintf(
            '%d.%02d',
            intdiv($cents, 100),
            $cents % 100,
        );
    }

    /**
     * Centralize validation exceptions for consistent API validation errors.
     */
    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => [$message],
        ]);
    }
}
