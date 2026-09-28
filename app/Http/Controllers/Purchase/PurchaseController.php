<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\PurchaseRequest;
use App\Models\Purchase;
use App\Services\Purchase\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(
        protected  PurchaseService $purchaseService
    ) {
    }

    /**
     * Display paginated purchases.
     */
    public function index(
        Request $request
    ): JsonResponse {
        $perPage = min(
            max(
                (int) $request->query('per_page', 15),
                1
            ),
            100
        );

        $search = $request->query('search');

        $status = $request->query('status');

        $purchases = $this->purchaseService->paginate(
            perPage: $perPage,
            search: $search,
            status: $status,
        );

        return response()->json([
            'success' => true,

            'message' =>
                'Purchases retrieved successfully.',

            'data' => $purchases->items(),

            'meta' => [
                'current_page' =>
                    $purchases->currentPage(),

                'last_page' =>
                    $purchases->lastPage(),

                'per_page' =>
                    $purchases->perPage(),

                'total' =>
                    $purchases->total(),
            ],
        ]);
    }

    /**
     * Store a purchase draft.
     */
    public function store(
        PurchaseRequest $request
    ): JsonResponse {
        $purchase = $this->purchaseService->create(
            data: $request->validated(),
            user: $request->user(),
        );

        return response()->json([
            'success' => true,

            'message' =>
                'Purchase draft created successfully.',

            'data' => $purchase,
        ], 201);
    }

    /**
     * Display a purchase.
     */
    public function show(
        int $id
    ): JsonResponse {
        $purchase = $this->purchaseService->findById($id);

        return response()->json([
            'success' => true,

            'message' =>
                'Purchase retrieved successfully.',

            'data' => $purchase,
        ]);
    }

    /**
     * Receive a purchase.
     */
    public function receive(
        int $id,
        Request $request
    ): JsonResponse {
        $purchase = $this->purchaseService->receive(
            purchaseId: $id,
            user: $request->user(),
        );

        return response()->json([
            'success' => true,

            'message' =>
                'Purchase received successfully.',

            'data' => $purchase,
        ]);
    }

   /**
 * Cancel a purchase.
 */
public function cancel(
    int $id,
    Request $request
): JsonResponse {
    $purchase = $this->purchaseService->cancel(
        purchaseId: $id,
        user: $request->user(),
    );

    return response()->json([
        'success' => true,
        'message' => 'Purchase cancelled successfully.',
        'data' => $purchase,
    ]);
}
}