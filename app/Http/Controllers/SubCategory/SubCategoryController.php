<?php

namespace App\Http\Controllers\SubCategory;

use App\Http\Controllers\Controller;
use App\Services\SubCategoryService\SubCategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\SubCategory;
use App\Http\Requests\SubCategoryRequest\SubCategoryRequest;
use App\Http\Resources\SubCategoryResource\SubCategoryResource;

class SubCategoryController extends Controller
{
    public function __construct(
        protected SubCategoryService $subCategoryService
    )
    {

    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $perPage = min(max((int) $perPage, 1), 100);
        $subCategories = $this->subCategoryService->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'SubCategories retrieved successfully.',
            'data' => SubCategoryResource::collection($subCategories->items()),
            'meta' => [
                'current_page' => $subCategories->currentPage(),
                'last_page' => $subCategories->lastPage(),
                'per_page' => $subCategories->perPage(),
                'total' => $subCategories->total(),
            ],
        ]);
    }

    public function store(SubCategoryRequest $request): JsonResponse
    {
         $subCategory = $this->subCategoryService->create($request->validated());
         
         return response()->json([
            'success' => true,
            'message' => 'SubCategory created successfully.',
            'data' => new SubCategoryResource($subCategory),
         ], 201);
    }

 public function show(SubCategory $subCategory): JsonResponse 
    {
        return response()->json([
            'success' => true,
            'message' => 'SubCategory retrieved successfully.',
            'data' => new SubCategoryResource($subCategory),
        ]);
    }

   public function update(SubCategoryRequest $request, int $id): JsonResponse
{
    $subCategory = $this->subCategoryService->update(
        $id,
        $request->validated()
    );

    return response()->json([
        'success' => true,
        'message' => 'SubCategory updated successfully.',
        'data' => new SubCategoryResource($subCategory),
    ]);
}

    public function destroy(int $id): JsonResponse
    {
        $this->subCategoryService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'SubCategory deleted successfully.',
        ]);
    }
}
