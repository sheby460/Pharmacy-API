<?php

namespace App\Http\Controllers\Customer;
use App\Services\CustomerService\CustomerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Customer\CustomerResource;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService
    )
    {  }

    public function index(Request $request): JsonResponse
    {
       $perPage = (int) $request->get('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $customers = $this->customerService->paginateCustomers($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Customers retrieved successfully.',
            'data' => CustomerResource::collection($customers->items()),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function store(CustomerRequest $request): JsonResponse {
        
        $customer = $this->customerService->createCustomer(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully.',
            'data' => new CustomerResource($customer),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $customer = $this->customerService->getCustomerById($id);

        return response()->json([
            'success' => true,
            'message' => 'Customer retrieved successfully.',
            'data' => new CustomerResource($customer),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $customer = $this->customerService->updateCustomer(
            $id,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully.',
            'data' => new CustomerResource($customer),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->customerService->deleteCustomer($id);

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully.',
        ]);
    }
}
