<?php

namespace App\Http\Controllers\Drugs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Drugs\DrugsRequest;
use App\Http\Resources\Drugs\DrugsResource;
use App\Models\Drug;
use App\Services\Drugs\DrugsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DrugsController extends Controller
{
    public function __construct(
        protected DrugsService $drugService
    )
    {
    }
    public function index(Request $request): JsonResponse {
        $perPage =$request->query('per_page', 15);
        $perPage = min(max((int) $perPage, 1), 100);
        $drugs = $this->drugService->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Drugs retrieved successfully',
            'data' => DrugsResource::collection($drugs->items()),
            'meta' => [
                'current_page' => $drugs->currentPage(),
                'last_page' => $drugs->lastPage(),
                'per_page' =>$drugs->perPage(),
                'total' => $drugs->total(),
            ],
        ]);
    }

    public function store(DrugsRequest $request): JsonResponse {
     $drugs = $this->drugService->create($request->validated());

     return response()->json([
        'success' => true,
        'message' => 'Drugs created successfully. ',
        'data' => new DrugsResource($drugs),
     ], 201);
    }

    public function show(Drug $drug): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => 'Drugs retrieved successfully.',
            'data' => new DrugsResource($drug),
        ]);
    }

    public function update(DrugsRequest $request, int $id ): JsonResponse
    {
      $drug =$this->drugService->update(
        $id,
        $request->validated()
      );

         return response()->json([
            'success' => true,
            'message' => 'Drugs updated successfully.',
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
