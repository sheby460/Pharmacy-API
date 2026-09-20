<?php

namespace App\Http\Controllers\StockMovements;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockMovements\StockAdjustmentRequest;
use App\Http\Resources\StockMovement\StockMovementsResource;
use App\Services\Inventory\StockMovementQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function __construct(
        protected StockMovementQueryService $service
    ) {
    }

    /**
     * Display a paginated list of stock movements.
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

        $type = $request->query('type');

        $drugId = $request->filled('drug_id')
            ? (int) $request->query('drug_id')
            : null;

        $movements = $this->service->paginate(
            $perPage,
            $search,
            $type,
            $drugId
        );

        return response()->json([
            'success' => true,

            'message' =>
                'Stock movements retrieved successfully.',

            'data' => StockMovementsResource::collection(
                $movements->items()
            ),

            'meta' => [
                'current_page' =>
                    $movements->currentPage(),

                'last_page' =>
                    $movements->lastPage(),

                'per_page' =>
                    $movements->perPage(),

                'total' =>
                    $movements->total(),
            ],
        ]);
    }

    /**
     * Display a single stock movement.
     */
    public function show(
        int $id
    ): JsonResponse {
        $movement = $this->service->findById($id);

        return response()->json([
            'success' => true,

            'message' =>
                'Stock movement retrieved successfully.',

            'data' => new StockMovementsResource(
                $movement
            ),
        ]);
    }

    /**
     * Create a stock adjustment.
     */
    public function adjust(
        StockAdjustmentRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $movementType = StockMovementType::from(
            $validated['movement_type']
        );

        /**
         * Use the injected $service property.
         */
        $stockMovement = $this->service->adjust(
            drugBatchId: (int) $validated['drug_batch_id'],
            movementType: $movementType,
            quantity: (int) $validated['quantity'],
            user: $request->user(),
            notes: $validated['notes'] ?? null,
        );

        $stockMovement->load([
            'drug',
            'drugBatch',
            'createdBy',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stock adjustment completed successfully.',
            'data' => new StockMovementsResource(
                $stockMovement
            ),
        ], 201);
    }
}