<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sale\StoreSaleReturnRequest;
use App\Http\Resources\Sale\SaleReturnResource;
use App\Services\Sales\SaleReturnQueryService;
use App\Services\Sales\SaleReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\Sale\ConfirmSaleReturnRefundRequest;

class SaleReturnController extends Controller
{
    public function __construct(
        private readonly SaleReturnService $returnService,
        private readonly SaleReturnQueryService $queryService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'refund_status' => [
                'nullable',
                'in:PENDING,REFUNDED,REJECTED',
            ],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return SaleReturnResource::collection(
            $this->queryService->paginate($filters)
        );
    }

    public function store(
        StoreSaleReturnRequest $request
    ): JsonResponse {
        $return = $this->returnService->create(
            $request->validated(),
            $request->user(),
        );

        return (new SaleReturnResource($return))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): SaleReturnResource
    {
        return new SaleReturnResource(
            $this->queryService->findById($id)
        );
    }

    public function returnableItems(int $saleId): JsonResponse
    {
        $data = $this->queryService->returnableItems($saleId);

        return response()->json([
            'success' => true,
            'data' => [
                'sale' => $data['sale'],
                'items' => $data['items'],
            ],
        ]);
    }

    public function confirmRefund(
    ConfirmSaleReturnRefundRequest $request,
    int $id,
): JsonResponse {
    $saleReturn = $this->returnService->confirmRefund(
        saleReturnId: $id,
        user: $request->user(),
        notes: $request->validated('notes'),
    );

    return response()->json([
        'success' => true,
        'message' => 'Refund confirmation recorded successfully.',
        'data' => new SaleReturnResource($saleReturn),
    ]);
}
}
