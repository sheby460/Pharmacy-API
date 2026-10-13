<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\PurchaseRequest;
use App\Http\Resources\Purchase\PurchaseResource;
use App\Models\Purchase;
use App\Services\Purchase\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:150'],
            'status' => [
                'sometimes',
                'nullable',
                'in:draft,received,cancelled',
            ],
        ]);

        $purchases = $this->purchaseService->paginate(
            perPage: (int) ($validated['per_page'] ?? 15),
            search: $validated['search'] ?? null,
            status: $validated['status'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Purchases retrieved successfully.',
            'data' => PurchaseResource::collection(
                $purchases->items()
            )->resolve($request),
            'meta' => [
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'per_page' => $purchases->perPage(),
                'total' => $purchases->total(),
            ],
        ]);
    }

    public function store(PurchaseRequest $request): JsonResponse
    {
        $purchase = $this->purchaseService->create(
            data: $request->validated(),
            user: $request->user(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Purchase draft created successfully.',
            'data' => (new PurchaseResource($purchase))->resolve($request),
        ], 201);
    }

    public function show(
        Purchase $purchase,
        Request $request
    ): JsonResponse {
        $purchase = $this->purchaseService->findById($purchase->id);

        return response()->json([
            'success' => true,
            'message' => 'Purchase retrieved successfully.',
            'data' => (new PurchaseResource($purchase))->resolve($request),
        ]);
    }

    public function receive(
        Purchase $purchase,
        Request $request
    ): JsonResponse {
        $purchase = $this->purchaseService->receive(
            purchaseId: $purchase->id,
            user: $request->user(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Purchase received successfully.',
            'data' => (new PurchaseResource($purchase))->resolve($request),
        ]);
    }

    public function cancel(
        Purchase $purchase,
        Request $request
    ): JsonResponse {
        $purchase = $this->purchaseService->cancel(
            purchaseId: $purchase->id,
            user: $request->user(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Purchase cancelled successfully.',
            'data' => (new PurchaseResource($purchase))->resolve($request),
        ]);
    }
}
