<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Services\Supplier\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService
    ) {  }

    public function index(Request $request): JsonResponse {
        $perPage = (int) $request->get('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $suppliers = $this->supplierService->paginateSuppliers($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Suppliers retrieved successfully.',
            'data' => SupplierResource::collection($suppliers->items()),
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
            ],
        ]);
    }

    public function store(SupplierRequest $request): JsonResponse
    {
        $supplier = $this->supplierService->createSupplier(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully.',
            'data' => new SupplierResource($supplier),
        ], 201);
    }

     public function show(int $id): JsonResponse
    {
        $supplier = $this->supplierService->getSupplierById($id);

        return response()->json([
            'success' => true,
            'message' => 'Supplier retrieved successfully.',
            'data' => new SupplierResource($supplier),
        ]);
    }

    public function update(SupplierRequest $request, int $id): JsonResponse
    {
        $supplier = $this->supplierService->updateSupplier(
            $id,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully.',
            'data' => new SupplierResource($supplier),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->supplierService->deleteSupplier($id);

        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully.',
        ]);
    }
}
