<?php

namespace App\Services\Sales;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleItem;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Http\Resources\Sale\SaleReturnableItemResource;

class SaleReturnQueryService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return SaleReturn::query()
            ->with([
                'sale:id,invoice_number,status',
                'processedBy:id,fname,mname,lname,username',
                'items.drug',
                'items.drugBatch',
            ])
            ->when(
                filled($filters['search'] ?? null),
                function ($query) use ($filters) {
                    $search = trim($filters['search']);

                    $query->where(function ($nested) use ($search) {
                        $nested
                            ->where('return_number', 'like', "%{$search}%")
                            ->orWhereHas('sale', function ($saleQuery) use ($search) {
                                $saleQuery->where(
                                    'invoice_number',
                                    'like',
                                    "%{$search}%"
                                );
                            });
                    });
                }
            )
            ->when(
                filled($filters['refund_status'] ?? null),
                fn ($query) => $query->where(
                    'refund_status',
                    $filters['refund_status']
                )
            )
            ->when(
                filled($filters['date_from'] ?? null),
                fn ($query) => $query->whereDate(
                    'created_at',
                    '>=',
                    $filters['date_from']
                )
            )
            ->when(
                filled($filters['date_to'] ?? null),
                fn ($query) => $query->whereDate(
                    'created_at',
                    '<=',
                    $filters['date_to']
                )
            )
            ->latest('id')
            ->paginate(
                min(max((int) ($filters['per_page'] ?? 15), 1), 100)
            );
    }

    public function findById(int $id): SaleReturn
    {
        return SaleReturn::query()
            ->with([
                'sale:id,invoice_number,status,customer_id,created_by',
                'sale.customer',
                'processedBy',
                'items.drug',
                'items.drugBatch',
                'items.saleItem',
            ])
            ->findOrFail($id);
    }

    public function returnableItems(int $saleId): array
{
    $sale = Sale::query()->findOrFail($saleId);

    if ($sale->status !== SaleStatus::COMPLETED) {
        throw ValidationException::withMessages([
            'sale_id' => ['Only completed sales can be returned.'],
        ]);
    }

    $items = SaleItem::query()
        ->where('sale_id', $sale->id)
        ->with(['drug', 'drugBatch'])
        ->withSum([
            'returnItems as returned_quantity' => function ($query) {
                $query->whereHas('saleReturn', function ($returnQuery) {
                    $returnQuery->where('status', 'COMPLETED');
                });
            },
        ], 'quantity')
        ->orderBy('id')
        ->get();

    return [
        'sale' => [
            'id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'status' => $sale->status->value,
            'total' => $sale->total,
        ],
        'items' => SaleReturnableItemResource::collection($items),
    ];
}
}
