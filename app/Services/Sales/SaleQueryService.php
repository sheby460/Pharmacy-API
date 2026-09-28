<?php

namespace App\Services\Sales;

use App\Models\Sale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\SaleStatus;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

class SaleQueryService
{
    /**
     * Get paginated sales.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Sale::query()
            ->with([
                'customer:id,name',
                'createdBy:id,name',
                'cancelledBy:id,name',
            ])
            ->withCount([
                'items',
                'payments',
            ]);

        $this->applySearchFilter($query, $filters);
        $this->applyStatusFilter($query, $filters);
        $this->applyCustomerFilter($query, $filters);
        $this->applyDateFilters($query, $filters);
        $this->applySorting($query, $filters);

        return $query
            ->paginate($this->resolvePerPage($perPage))
            ->withQueryString();
    }

    /**
     * Find a sale by its ID.
     */
    public function findById(int $saleId): Sale
    {
        return Sale::query()
            ->with([
                'customer',
                'createdBy',
                'cancelledBy',
                'items.drug',
                'items.drugBatch',
                'payments',
            ])
            ->findOrFail($saleId);
    }

    /**
     * Find a sale by its invoice number.
     */
    public function findByInvoiceNumber(
        string $invoiceNumber
    ): Sale {
        return Sale::query()
            ->with([
                'customer',
                'createdBy',
                'cancelledBy',
                'items.drug',
                'items.drugBatch',
                'payments',
            ])
            ->where('invoice_number', $invoiceNumber)
            ->firstOrFail();
    }

    /**
     * Apply invoice or customer search.
     *
     * @param array<string, mixed> $filters
     */
    private function applySearchFilter(
        Builder $query,
        array $filters
    ): void {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($search): void {
            $builder
                ->where('invoice_number', 'like', "%{$search}%")
                ->orWhereHas(
                    'customer',
                    function (Builder $customerQuery) use ($search): void {
                        $customerQuery
                            ->where('name', 'like', "%{$search}%");
                    }
                );
        });
    }

    /**
     * Apply sale status filtering.
     *
     * @param array<string, mixed> $filters
     */
    private function applyStatusFilter(
        Builder $query,
        array $filters
    ): void {
        $status = $filters['status'] ?? null;

        if ($status === null || $status === '') {
            return;
        }
        $validateStatuses = array_map(
            static fn(SaleStatus $saleStatus): string => $saleStatus->value,
            SaleStatus::cases()
        );
        if (!in_array($status, $validateStatuses, true)) {
            return;
        }

        $query->where('status', $status);
    }

    /**
     * Apply customer filtering.
     *
     * @param array<string, mixed> $filters
     */
    private function applyCustomerFilter(
        Builder $query,
        array $filters
    ): void {
        $customerId = $filters['customer_id'] ?? null;

        if ($customerId === null || $customerId === '') {
            return;
        }

        $query->where('customer_id', $customerId);
    }


    /**
     * Apply date range filtering.
     *
     * @param array<string, mixed> $filters
     */
    private function applyDateFilters(
        Builder $query,
        array $filters
    ): void {
        $dateFrom = $this->parseDateFilter(
            $filters['date_from'] ?? null
        );

        $dateTo = $this->parseDateFilter(
            $filters['date_to'] ?? null
        );

        if ($dateFrom !== null) {
            $query->whereDate(
                'created_at',
                '>=',
                $dateFrom
            );
        }

        if ($dateTo !== null) {
            $query->whereDate(
                'created_at',
                '<=',
                $dateTo
            );
        }
    }

    /**
     * Parse a date filter safely.
     */
    private function parseDateFilter(
        mixed $value
    ): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat(
                'Y-m-d',
                $value
            )->format('Y-m-d');
        } catch (InvalidFormatException) {
            return null;
        }
    }

    /**
     * Apply safe sorting.
     *
     * @param array<string, mixed> $filters
     */
    private function applySorting(
        Builder $query,
        array $filters
    ): void {
        $allowedSortColumns = [
            'created_at',
            'total',
            'invoice_number',
            'status',
        ];

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = strtolower(
            (string) ($filters['sort_direction'] ?? 'desc')
        );

        if (!in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = 'created_at';
        }

        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortBy, $sortDirection);
    }

    /**
     * Restrict the pagination size.
     */
    private function resolvePerPage(int $perPage): int
    {
        return max(1, min($perPage, 100));
    }
}
