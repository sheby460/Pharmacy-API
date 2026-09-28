<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sale\SaleCancellationRequest;
use App\Http\Requests\Sale\SaleRequest;
use App\Http\Resources\Sale\SaleResource;
use App\Services\Sales\SaleQueryService;
use App\Services\Sales\SaleCancellationService;
use App\Services\Sales\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SaleController extends Controller
{
    public function __construct(
        private readonly SaleService $saleService,
        private readonly SaleQueryService $saleQueryService,
        private readonly SaleCancellationService $saleCancellationService,
    ) {
    }

    /**
     * Display a paginated list of sales.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                'string',
                'max:30',
            ],

            'customer_id' => [
                'nullable',
                'integer',
                'exists:customers,id',
            ],

            'date_from' => [
                'nullable',
                'date',
            ],

            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],

            'sort_by' => [
                'nullable',
                'string',
                'in:created_at,total,invoice_number,status',
            ],

            'sort_direction' => [
                'nullable',
                'string',
                'in:asc,desc',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 15);

        $sales = $this->saleQueryService->paginate(
            filters: $filters,
            perPage: $perPage,
        );

        return SaleResource::collection($sales);
    }

    /**
     * Create a new sale.
     */
    public function store(SaleRequest $request): JsonResponse
    {
        $sale = $this->saleService->create(
            data: $request->validated(),
            user: $request->user(),
        );

        return (new SaleResource($sale))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display a single sale.
     */
    public function show(int $id): SaleResource
    {
        $sale = $this->saleQueryService->findById($id);

        return new SaleResource($sale);
    }

    /**
     * Find a sale using its invoice number.
     */
    public function showByInvoice(string $invoice): SaleResource
    {
        $sale = $this->saleQueryService
            ->findByInvoiceNumber($invoice);

        return new SaleResource($sale);
    }

    /**
     * Cancel a completed sale.
     */
    public function cancel(
        SaleCancellationRequest $request,
        int $id,
    ): JsonResponse {
        $sale = $this->saleCancellationService->cancel(
            saleId: $id,
            user: $request->user(),
            reason: $request->validated('reason'),
        );

        return response()->json([
            'message' => 'Sale cancelled successfully.',
            'data' => new SaleResource($sale),
        ], Response::HTTP_OK);
    }
}