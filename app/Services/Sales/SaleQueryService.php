<?php

namespace App\Services\Sales;

use App\Enums\SaleStatus;
use App\Models\Sale;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SaleQueryService
{
    /**
     * Get paginated sales.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(
        array $filters = [],
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = Sale::query()
            ->with([
                'customer:id,customer_name',
                'createdBy:id,fname,mname,lname,username',
                'cancelledBy:id,fname,mname,lname,username',
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
            ->orderByDesc('id')
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
    public function findByInvoiceNumber(string $invoiceNumber): Sale
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
            ->where('invoice_number', $invoiceNumber)
            ->firstOrFail();
    }

    /**
     * Search by invoice number or customer name.
     *
     * @param array<string, mixed> $filters
     */
    private function applySearchFilter(
        Builder $query,
        array $filters,
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
                        $customerQuery->where(
                            'customer_name',
                            'like',
                            "%{$search}%"
                        );
                    }
                );
        });
    }

    /**
     * Filter by sale status.
     *
     * @param array<string, mixed> $filters
     */
    private function applyStatusFilter(
        Builder $query,
        array $filters,
    ): void {
        $status = $filters['status'] ?? null;

        if ($status === null || $status === '') {
            return;
        }

        $validStatuses = array_map(
            static fn (SaleStatus $saleStatus): string => $saleStatus->value,
            SaleStatus::cases(),
        );

        if (in_array($status, $validStatuses, true)) {
            $query->where('status', $status);
        }
    }

    /**
     * Filter by customer.
     *
     * @param array<string, mixed> $filters
     */
    private function applyCustomerFilter(
        Builder $query,
        array $filters,
    ): void {
        $customerId = $filters['customer_id'] ?? null;

        if ($customerId !== null && $customerId !== '') {
            $query->where('customer_id', $customerId);
        }
    }

    /**
     * Apply date range filtering.
     *
     * @param array<string, mixed> $filters
     */
    private function applyDateFilters(
        Builder $query,
        array $filters,
    ): void {
        $dateFrom = $this->parseDateFilter(
            $filters['date_from'] ?? null
        );

        $dateTo = $this->parseDateFilter(
            $filters['date_to'] ?? null
        );

        if ($dateFrom !== null) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
    }

    /**
     * Parse a date filter.
     */
    private function parseDateFilter(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);

            return $date && $date->format('Y-m-d') === $value
                ? $date->format('Y-m-d')
                : null;
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
        array $filters,
    ): void {
        $allowedColumns = [
            'created_at',
            'total',
            'invoice_number',
            'status',
        ];

        $sortBy = $filters['sort_by'] ?? 'created_at';

        $direction = strtolower(
            (string) ($filters['sort_direction'] ?? 'desc')
        );

        if (!in_array($sortBy, $allowedColumns, true)) {
            $sortBy = 'created_at';
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $query->orderBy($sortBy, $direction);
    }

    /**
     * Restrict pagination size.
     */
    private function resolvePerPage(int $perPage): int
    {
        return max(1, min($perPage, 100));
    }
}
