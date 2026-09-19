<?php

namespace App\Http\Controllers\DrugBatches;

use App\Http\Controllers\Controller;
use App\Http\Requests\DrugBatches\DrugBatchRequest;
use App\Http\Resources\DrugBatch\DrugBatchResource;
use App\Services\DrugBatches\DrugBatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DrugBatchController extends Controller
{
    public function __construct(
        protected DrugBatchService $batchService
    ) {}

    public function index(
        Request $request
    ): JsonResponse {
        $perPage = min(
            max(
                (int) $request->query(
                    'per_page',
                    15
                ),
                1
            ),
            100
        );

        $search = $request->query('search');

        $drugId = $request->filled('drug_id')
            ? (int) $request->query('drug_id')
            : null;

        $batches = $this->batchService->paginate(
            $perPage,
            $search,
            $drugId
        );

        return response()->json([
            'success' => true,
            'message' =>
                'Drug batches retrieved successfully.',
            'data' => DrugBatchResource::collection(
                $batches->items()
            ),
            'meta' => [
                'current_page' =>
                    $batches->currentPage(),
                'last_page' =>
                    $batches->lastPage(),
                'per_page' =>
                    $batches->perPage(),
                'total' =>
                    $batches->total(),
            ],
        ]);
    }

    public function store(
        DrugBatchRequest $request
    ): JsonResponse {
        $batch = $this->batchService->create(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' =>
                'Drug batch created successfully.',
            'data' =>
                new DrugBatchResource($batch),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $batch =
            $this->batchService->findById($id);

        return response()->json([
            'success' => true,
            'message' =>
                'Drug batch retrieved successfully.',
            'data' =>
                new DrugBatchResource($batch),
        ]);
    }

    public function byDrug(
        Request $request,
        int $drugId
    ): JsonResponse {
        $perPage = min(
            max(
                (int) $request->query(
                    'per_page',
                    15
                ),
                1
            ),
            100
        );

        $batches =
            $this->batchService->getByDrug(
                $drugId,
                $perPage
            );

        return response()->json([
            'success' => true,
            'message' =>
                'Drug batches retrieved successfully.',
            'data' =>
                DrugBatchResource::collection(
                    $batches->items()
                ),
            'meta' => [
                'current_page' =>
                    $batches->currentPage(),
                'last_page' =>
                    $batches->lastPage(),
                'per_page' =>
                    $batches->perPage(),
                'total' =>
                    $batches->total(),
            ],
        ]);
    }
}