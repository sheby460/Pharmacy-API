<?php

namespace App\Services\Sales;

use App\Enums\SaleStatus;
use App\Models\Sale;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class SaleQueryService
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    private const ALLOWED_SORT_COLUMNS = [
        'created_at',
        'total',
        'invoice_number',
        'status',
    ];

    /**
     * Retrieve paginated sales using validated filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(
        array $filters = [],
        int $perPage = self::DEFAULT_PER_PAGE,
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
     * Retrieve a sale by its primary key.
     */
    public function findById(int $saleId): Sale
    {
        return Sale::query()
            ->with($this->saleRelationships())
            ->findOrFail($saleId);
    }

    /**
     * Retrieve a sale by its invoice number.
     */
    public function findByInvoiceNumber(string $invoiceNumber): Sale
    {
        $invoiceNumber = trim($invoiceNumber);

        if ($invoiceNumber === '') {
            throw ValidationException::withMessages([
                'invoice' => ['An invoice number is required.'],
            ]);
        }

        return Sale::query()
            ->with($this->saleRelationships())
            ->where('invoice_number', $invoiceNumber)
            ->firstOrFail();
    }

    /**
     * Relationships required when returning a single sale.
     *
     * @return array<int, string>
     */
    private function saleRelationships(): array
    {
        return [
            'customer',
            'createdBy',
            'cancelledBy',
            'items.drug',
            'items.drugBatch',
            'payments',
        ];
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
                ->where(
                    'invoice_number',
                    'like',
                    "%{$search}%",
                )
                ->orWhereHas(
                    'customer',
                    function (Builder $customerQuery) use ($search): void {
                        $customerQuery->where(
                            'customer_name',
                            'like',
                            "%{$search}%",
                        );
                    },
                );
        });
    }

    /**
     * Filter by sale status.
     *
     * The controller validates the incoming status. This service also
     * validates it because it may be called from other application code.
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

        if (! is_string($status)) {
            throw ValidationException::withMessages([
                'status' => ['The selected sale status is invalid.'],
            ]);
        }

        $validStatuses = array_map(
            static fn (SaleStatus $saleStatus): string => $saleStatus->value,
            SaleStatus::cases(),
        );

        if (! in_array($status, $validStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => ['The selected sale status is invalid.'],
            ]);
        }

        $query->where('status', $status);
    }

    /**
     * Filter by customer ID.
     *
     * @param array<string, mixed> $filters
     */
    private function applyCustomerFilter(
        Builder $query,
        array $filters,
    ): void {
        $customerId = $filters['customer_id'] ?? null;

        if ($customerId === null || $customerId === '') {
            return;
        }

        $validatedCustomerId = filter_var(
            $customerId,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if ($validatedCustomerId === false) {
            throw ValidationException::withMessages([
                'customer_id' => [
                    'The customer ID must be a positive integer.',
                ],
            ]);
        }

        $query->where('customer_id', $validatedCustomerId);
    }

    /**
     * Filter by inclusive calendar dates.
     *
     * Uses a start-inclusive, end-exclusive range on created_at so the
     * database can make better use of an index on that column.
     *
     * @param array<string, mixed> $filters
     */
    private function applyDateFilters(
        Builder $query,
        array $filters,
    ): void {
        $dateFrom = $this->parseDateFilter(
            $filters['date_from'] ?? null,
            'date_from',
        );

        $dateTo = $this->parseDateFilter(
            $filters['date_to'] ?? null,
            'date_to',
        );

        if ($dateFrom !== null) {
            $query->where(
                'created_at',
                '>=',
                $dateFrom->startOfDay()->toDateTimeString(),
            );
        }

        if ($dateTo !== null) {
            $query->where(
                'created_at',
                '<',
                $dateTo->addDay()->startOfDay()->toDateTimeString(),
            );
        }
    }

    /**
     * Parse a strict YYYY-MM-DD date.
     */
    private function parseDateFilter(
        mixed $value,
        string $field,
    ): ?Carbon {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw ValidationException::withMessages([
                $field => ['The date must use the YYYY-MM-DD format.'],
            ]);
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);
        } catch (InvalidFormatException) {
            $date = false;
        }

        if (
            $date === false
            || $date->format('Y-m-d') !== $value
        ) {
            throw ValidationException::withMessages([
                $field => ['The date must be a valid date in YYYY-MM-DD format.'],
            ]);
        }

        return $date;
    }

    /**
     * Apply an allowlisted sort column and direction.
     *
     * @param array<string, mixed> $filters
     */
    private function applySorting(
        Builder $query,
        array $filters,
    ): void {
        $sortBy = $filters['sort_by'] ?? 'created_at';

        $direction = strtolower(
            (string) ($filters['sort_direction'] ?? 'desc'),
        );

        if (
            ! is_string($sortBy)
            || ! in_array($sortBy, self::ALLOWED_SORT_COLUMNS, true)
        ) {
            throw ValidationException::withMessages([
                'sort_by' => ['The selected sort column is invalid.'],
            ]);
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw ValidationException::withMessages([
                'sort_direction' => [
                    'Sort direction must be asc or desc.',
                ],
            ]);
        }

        $query->orderBy($sortBy, $direction);
    }

    /**
     * Keep pagination within supported limits.
     */
    private function resolvePerPage(int $perPage): int
    {
        return max(1, min($perPage, self::MAX_PER_PAGE));
    }
}
