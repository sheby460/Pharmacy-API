<?php

namespace App\Services\Sales;

use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\DrugBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SaleReturnService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function create(array $data, User $user): SaleReturn
    {
        $this->validateInput($data);

        return DB::transaction(function () use ($data, $user): SaleReturn {
            // Lock the sale to serialize returns against this invoice.
            $sale = Sale::query()
                ->whereKey((int) $data['sale_id'])
                ->lockForUpdate()
                ->first();

            if (! $sale) {
                $this->fail('sale_id', 'The selected sale does not exist.');
            }

            if ($sale->status !== SaleStatus::COMPLETED) {
                $this->fail(
                    'sale_id',
                    'Only completed sales can be returned.',
                );
            }

            $requestedItems = collect($data['items']);
            $saleItemIds = $requestedItems
                ->pluck('sale_item_id')
                ->map(fn($id): int => (int) $id);

            if ($saleItemIds->unique()->count() !== $saleItemIds->count()) {
                $this->fail(
                    'items',
                    'Each sale item may appear only once in a return.',
                );
            }

            $saleItems = SaleItem::query()
                ->where('sale_id', $sale->id)
                ->whereIn('id', $saleItemIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($saleItems->count() !== $saleItemIds->count()) {
                $this->fail(
                    'items',
                    'One or more items do not belong to this sale.',
                );
            }

            $subtotalCents = $this->toCents($sale->subtotal);
            $saleDiscountCents = $this->toCents($sale->discount);
            $saleTaxCents = $this->toCents($sale->tax);

            if (
                $subtotalCents <= 0 ||
                $saleDiscountCents > $subtotalCents
            ) {
                throw new InvalidArgumentException(
                    'The original sale financial data is inconsistent.'
                );
            }

            /*
             * Determine each item's share of the sale-level discount/tax.
             * Item shares are allocated in ascending item ID order; the
             * final item receives the exact remaining cents.
             */
            $itemAllocations = $this->allocateSaleAdjustments(
                $saleItems->sortBy('id')->values(),
                $subtotalCents,
                $saleDiscountCents,
                $saleTaxCents,
            );

            $preparedItems = [];
            $refundCents = 0;

            foreach ($requestedItems as $index => $requestedItem) {
                $saleItem = $saleItems->get(
                    (int) $requestedItem['sale_item_id']
                );

                $quantity = (int) $requestedItem['quantity'];
                $soldQuantity = (int) $saleItem->quantity;

                if ($soldQuantity <= 0) {
                    throw new InvalidArgumentException(
                        "Sale item {$saleItem->id} has an invalid quantity."
                    );
                }

                $previous = $this->previousReturns($saleItem);

                if ($previous['quantity'] > $soldQuantity) {
                    throw new InvalidArgumentException(
                        "Return history exceeds the quantity sold for item {$saleItem->id}."
                    );
                }

                $remainingQuantity = $soldQuantity - $previous['quantity'];

                if ($quantity > $remainingQuantity) {
                    $this->fail(
                        "items.{$index}.quantity",
                        "Only {$remainingQuantity} unit(s) remain returnable.",
                    );
                }

                $allocation = $itemAllocations[$saleItem->id];

                $grossCents = $this->allocateAmount(
                    originalCents: $this->toCents($saleItem->subtotal),
                    previouslyReturnedCents: $previous['gross_cents'],
                    quantity: $quantity,
                    remainingQuantity: $remainingQuantity,
                    soldQuantity: $soldQuantity,
                );

                $discountCents = $this->allocateAmount(
                    originalCents: $allocation['discount_cents'],
                    previouslyReturnedCents: $previous['discount_cents'],
                    quantity: $quantity,
                    remainingQuantity: $remainingQuantity,
                    soldQuantity: $soldQuantity,
                );

                $taxCents = $this->allocateAmount(
                    originalCents: $allocation['tax_cents'],
                    previouslyReturnedCents: $previous['tax_cents'],
                    quantity: $quantity,
                    remainingQuantity: $remainingQuantity,
                    soldQuantity: $soldQuantity,
                );

                $lineTotalCents = $grossCents - $discountCents + $taxCents;

                if ($lineTotalCents < 0) {
                    throw new InvalidArgumentException(
                        'The calculated return amount cannot be negative.'
                    );
                }

                $disposition = strtoupper(
                    trim((string) $requestedItem['disposition'])
                );

                if (! in_array($disposition, ['RESTOCK', 'QUARANTINE'], true)) {
                    $this->fail(
                        "items.{$index}.disposition",
                        'Disposition must be RESTOCK or QUARANTINE.',
                    );
                }

                if (! $saleItem->drug_batch_id) {
                    $this->fail(
                        "items.{$index}.sale_item_id",
                        'The original drug batch is missing.',
                    );
                }

                $preparedItems[] = [
                    'index' => $index,
                    'sale_item' => $saleItem,
                    'quantity' => $quantity,
                    'gross_cents' => $grossCents,
                    'discount_cents' => $discountCents,
                    'tax_cents' => $taxCents,
                    'line_total_cents' => $lineTotalCents,
                    'disposition' => $disposition,
                    'notes' => $requestedItem['notes'] ?? null,
                ];

                if ($lineTotalCents > PHP_INT_MAX - $refundCents) {
                    throw new InvalidArgumentException(
                        'The return total exceeds the supported amount.'
                    );
                }

                $refundCents += $lineTotalCents;
            }

            // Do not let this return exceed the amount still refundable.
            $previousSaleRefundCents = $this->previousSaleRefundCents($sale);

            $saleTotalCents = $this->toCents($sale->total);
            $remainingSaleRefundCents =
                $saleTotalCents - $previousSaleRefundCents;

            if (
                $remainingSaleRefundCents < 0 ||
                $refundCents > $remainingSaleRefundCents
            ) {
                $this->fail(
                    'items',
                    'The calculated refund exceeds the remaining refundable sale total.',
                );
            }

            $return = SaleReturn::query()->create([
                'return_number' => 'RET-' . (string) Str::ulid(),
                'sale_id' => $sale->id,
                'processed_by' => $user->id,
                'status' => 'COMPLETED',
                'refund_method' => isset($data['refund_method'])
                    ? strtoupper(trim($data['refund_method']))
                    : null,
                'refund_status' => 'PENDING',
                'refund_amount' => $this->fromCents($refundCents),
                'reason' => trim($data['reason']),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($preparedItems as $prepared) {
                /** @var SaleItem $saleItem */
                $saleItem = $prepared['sale_item'];

                $returnItem = SaleReturnItem::query()->create([
                    'sale_return_id' => $return->id,
                    'sale_item_id' => $saleItem->id,
                    'drug_id' => $saleItem->drug_id,
                    'drug_batch_id' => $saleItem->drug_batch_id,
                    'quantity' => $prepared['quantity'],
                    'unit_price' => $this->fromCents(
                        $this->toCents($saleItem->unit_price)
                    ),
                    'discount' => $this->fromCents(
                        $prepared['discount_cents']
                    ),
                    'tax' => $this->fromCents(
                        $prepared['tax_cents']
                    ),
                    'line_total' => $this->fromCents(
                        $prepared['line_total_cents']
                    ),
                    'disposition' => $prepared['disposition'],
                    'notes' => $prepared['notes'],
                ]);

                /*
                 * Quarantine is recorded but never increases sellable stock.
                 * RESTOCK explicitly increases the original batch.
                 *
                 * For strict pharmacy controls, RESTOCK should only be
                 * permitted after an authorized inspection/approval step.
                 */
                if ($prepared['disposition'] === 'RESTOCK') {
                    $batch = DrugBatch::query()
                        ->whereKey($saleItem->drug_batch_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $batch) {
                        $this->fail(
                            "items.{$prepared['index']}.sale_item_id",
                            'The original drug batch could not be found.',
                        );
                    }

                    if ((int) $batch->drug_id !== (int) $saleItem->drug_id) {
                        $this->fail(
                            "items.{$prepared['index']}.sale_item_id",
                            'The original batch does not belong to the sold drug.',
                        );
                    }

                    $this->inventoryService->increase(
                        drugBatch: $batch,
                        quantity: $prepared['quantity'],
                        movementType: StockMovementType::SALE_RETURN,
                        user: $user,
                        referenceType: SaleReturn::class,
                        referenceId: $return->id,
                        notes: sprintf(
                            'Return %s; return item %d.',
                            $return->return_number,
                            $returnItem->id,
                        ),
                    );
                }
            }

            return $return->fresh([
                'sale.customer',
                'sale.createdBy',
                'processedBy',
                'items.drug',
                'items.drugBatch',
                'items.saleItem',
            ]);
        }, 5);
    }
    public function confirmRefund(
        int $saleReturnId,
        User $user,
        ?string $notes = null,
    ): SaleReturn {
        return DB::transaction(function () use (
            $saleReturnId,
            $user,
            $notes,
        ): SaleReturn {
            $saleReturn = SaleReturn::query()
                ->lockForUpdate()
                ->findOrFail($saleReturnId);

            if ($saleReturn->status !== 'COMPLETED') {
                throw ValidationException::withMessages([
                    'sale_return' => [
                        'Only completed returns can be refunded.',
                    ],
                ]);
            }

            if ($saleReturn->refund_status === 'REFUNDED') {
                throw ValidationException::withMessages([
                    'refund_status' => [
                        'This return has already been refunded.',
                    ],
                ]);
            }

            if ($saleReturn->refund_status !== 'PENDING') {
                throw ValidationException::withMessages([
                    'refund_status' => [
                        'This return is not eligible for refund confirmation.',
                    ],
                ]);
            }

            if ((float) $saleReturn->refund_amount <= 0) {
                throw ValidationException::withMessages([
                    'refund_amount' => [
                        'A refund amount greater than zero is required.',
                    ],
                ]);
            }

            $saleReturn->refund_status = 'REFUNDED';
            $saleReturn->refunded_at = now();

            if ($notes !== null && trim($notes) !== '') {
                $existingNotes = trim((string) $saleReturn->notes);

                $refundNote = sprintf(
                    'Refund confirmed by user #%d: %s',
                    $user->id,
                    trim($notes),
                );

                $saleReturn->notes = $existingNotes === ''
                    ? $refundNote
                    : $existingNotes . PHP_EOL . $refundNote;
            } else {
                $refundNote = sprintf(
                    'Refund confirmed by user #%d.',
                    $user->id,
                );

                $existingNotes = trim((string) $saleReturn->notes);

                $saleReturn->notes = $existingNotes === ''
                    ? $refundNote
                    : $existingNotes . PHP_EOL . $refundNote;
            }

            $saleReturn->save();

            return $saleReturn->fresh([
                'sale',
                'processedBy',
                'items.drug',
                'items.drugBatch',
                'items.saleItem',
            ]);
        }, 5);
    }
    /**
     * Allocate sale-level discount/tax among sale items.
     *
     * @param Collection<int, SaleItem> $items
     * @return array<int, array{discount_cents:int,tax_cents:int}>
     */
    private function allocateSaleAdjustments(
        Collection $items,
        int $subtotalCents,
        int $discountCents,
        int $taxCents,
    ): array {
        $allocations = [];
        $allocatedDiscount = 0;
        $allocatedTax = 0;
        $lastIndex = $items->count() - 1;

        foreach ($items->values() as $index => $item) {
            $itemSubtotal = $this->toCents($item->subtotal);

            if ($itemSubtotal < 0 || $itemSubtotal > $subtotalCents) {
                throw new InvalidArgumentException(
                    'A sale item subtotal is inconsistent with the sale subtotal.'
                );
            }

            if ($index === $lastIndex) {
                $itemDiscount = $discountCents - $allocatedDiscount;
                $itemTax = $taxCents - $allocatedTax;
            } else {
                $itemDiscount = (int) round(
                    ($discountCents * $itemSubtotal) / $subtotalCents,
                    0,
                    PHP_ROUND_HALF_UP
                );

                $itemTax = (int) round(
                    ($taxCents * $itemSubtotal) / $subtotalCents,
                    0,
                    PHP_ROUND_HALF_UP
                );

                $allocatedDiscount += $itemDiscount;
                $allocatedTax += $itemTax;
            }

            $allocations[$item->id] = [
                'discount_cents' => $itemDiscount,
                'tax_cents' => $itemTax,
            ];
        }

        return $allocations;
    }

    /**
     * Aggregate quantities and amounts already returned for a sale item.
     *
     * @return array{
     *   quantity:int,
     *   gross_cents:int,
     *   discount_cents:int,
     *   tax_cents:int
     * }
     */
    private function previousReturns(SaleItem $saleItem): array
    {
        $totals = SaleReturnItem::query()
            ->where('sale_item_id', $saleItem->id)
            ->whereHas(
                'saleReturn',
                fn($query) =>
                $query->where('status', 'COMPLETED')
            )
            ->selectRaw('COALESCE(SUM(quantity), 0) AS quantity')
            ->selectRaw('COALESCE(SUM(line_total + discount - tax), 0) AS gross')
            ->selectRaw('COALESCE(SUM(discount), 0) AS discount')
            ->selectRaw('COALESCE(SUM(tax), 0) AS tax')
            ->first();

        return [
            'quantity' => (int) $totals->quantity,
            'gross_cents' => $this->toCents($totals->gross),
            'discount_cents' => $this->toCents($totals->discount),
            'tax_cents' => $this->toCents($totals->tax),
        ];
    }

    private function previousSaleRefundCents(Sale $sale): int
    {
        $amount = SaleReturn::query()
            ->where('sale_id', $sale->id)
            ->where('status', 'COMPLETED')
            ->sum('refund_amount');

        return $this->toCents($amount);
    }

    private function allocateAmount(
        int $originalCents,
        int $previouslyReturnedCents,
        int $quantity,
        int $remainingQuantity,
        int $soldQuantity,
    ): int {
        if (
            $soldQuantity < 1 ||
            $remainingQuantity < 1 ||
            $quantity < 1 ||
            $quantity > $remainingQuantity
        ) {
            throw new InvalidArgumentException(
                'Invalid quantities supplied for return amount allocation.'
            );
        }

        if (
            $originalCents < 0 ||
            $previouslyReturnedCents < 0 ||
            $previouslyReturnedCents > $originalCents
        ) {
            throw new InvalidArgumentException(
                'Previously returned amounts exceed the original amount.'
            );
        }

        $remainingCents = $originalCents - $previouslyReturnedCents;

        if ($quantity === $remainingQuantity) {
            return $remainingCents;
        }

        return (int) round(
            ($remainingCents * $quantity) / $remainingQuantity,
            0,
            PHP_ROUND_HALF_UP
        );
    }

    private function validateInput(array $data): void
    {
        if (
            ! isset($data['sale_id']) ||
            filter_var($data['sale_id'], FILTER_VALIDATE_INT) === false ||
            (int) $data['sale_id'] < 1
        ) {
            $this->fail('sale_id', 'A valid sale ID is required.');
        }

        if (
            ! isset($data['reason']) ||
            ! is_string($data['reason']) ||
            trim($data['reason']) === '' ||
            mb_strlen(trim($data['reason'])) > 255
        ) {
            $this->fail(
                'reason',
                'A return reason is required and must not exceed 255 characters.',
            );
        }

        if (
            isset($data['notes']) &&
            $data['notes'] !== null &&
            ! is_string($data['notes'])
        ) {
            $this->fail('notes', 'Notes must be a string.');
        }

        if (
            isset($data['refund_method']) &&
            $data['refund_method'] !== null &&
            ! in_array(
                strtoupper(trim((string) $data['refund_method'])),
                ['CASH', 'MOBILE_MONEY', 'CARD'],
                true
            )
        ) {
            $this->fail('refund_method', 'The refund method is invalid.');
        }

        if (
            ! isset($data['items']) ||
            ! is_array($data['items']) ||
            count($data['items']) === 0
        ) {
            $this->fail('items', 'At least one return item is required.');
        }

        foreach ($data['items'] as $index => $item) {
            if (! is_array($item)) {
                $this->fail("items.{$index}", 'The return item is invalid.');
            }

            foreach (['sale_item_id', 'quantity'] as $field) {
                if (
                    ! isset($item[$field]) ||
                    filter_var($item[$field], FILTER_VALIDATE_INT) === false ||
                    (int) $item[$field] < 1
                ) {
                    $this->fail(
                        "items.{$index}.{$field}",
                        "{$field} must be a positive integer.",
                    );
                }
            }

            if (
                ! isset($item['disposition']) ||
                ! in_array(
                    strtoupper(trim((string) $item['disposition'])),
                    ['RESTOCK', 'QUARANTINE'],
                    true
                )
            ) {
                $this->fail(
                    "items.{$index}.disposition",
                    'Disposition must be RESTOCK or QUARANTINE.',
                );
            }

            if (
                isset($item['notes']) &&
                $item['notes'] !== null &&
                ! is_string($item['notes'])
            ) {
                $this->fail(
                    "items.{$index}.notes",
                    'Item notes must be a string.',
                );
            }
        }
    }
    private function toCents(string|int|float $amount): int
    {
        $value = trim((string) $amount);

        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $value, $matches)) {
            throw new InvalidArgumentException(
                'Money values must be non-negative decimals with at most two decimal places.'
            );
        }

        $whole = (int) $matches[1];
        $fraction = (int) str_pad($matches[2] ?? '', 2, '0');

        if ($whole > intdiv(PHP_INT_MAX - $fraction, 100)) {
            throw new InvalidArgumentException(
                'The monetary amount exceeds the supported range.'
            );
        }

        return ($whole * 100) + $fraction;
    }

    private function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => [$message],
        ]);
    }
}
