<?php

namespace App\Http\Controllers\Drugs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Drugs\DrugsRequest;
use App\Http\Resources\Drugs\DrugsResource;
use App\Services\Drugs\DrugsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DrugsController extends Controller
{
    public function __construct(
        protected DrugsService $drugService
    ) {}

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

        $drugs = $this->drugService->paginate(
            $perPage,
            $search
        );

        return response()->json([
            'success' => true,
            'message' => 'Drugs retrieved successfully.',
            'data' => DrugsResource::collection(
                $drugs->items()
            ),
            'meta' => [
                'current_page' => $drugs->currentPage(),
                'last_page' => $drugs->lastPage(),
                'per_page' => $drugs->perPage(),
                'total' => $drugs->total(),
            ],
        ]);
    }

    public function store(
        DrugsRequest $request
    ): JsonResponse {
        $drug = $this->drugService->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Drug created successfully.',
            'data' => new DrugsResource($drug),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $drug = $this->drugService->findById($id);

        return response()->json([
            'success' => true,
            'message' => 'Drug retrieved successfully.',
            'data' => new DrugsResource($drug),
        ]);
    }

    public function update(
        DrugsRequest $request,
        int $id
    ): JsonResponse {
        $drug = $this->drugService->update(
            $id,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Drug updated successfully.',
            'data' => new DrugsResource($drug),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->drugService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Drug deleted successfully.',
        ]);
    }
}
