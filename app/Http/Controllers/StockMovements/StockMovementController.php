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
        $filters = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'nullable', 'string', 'max:150'],
            'type' => ['sometimes', 'nullable', Rule::enum(StockMovementType::class)],
            'drug_id' => ['sometimes', 'nullable', 'integer', 'exists:drugs,id'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 15);
        $search = $filters['search'] ?? null;
        $type = $filters['type'] ?? null;
        $drugId = isset($filters['drug_id'])
            ? (int) $filters['drug_id']
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