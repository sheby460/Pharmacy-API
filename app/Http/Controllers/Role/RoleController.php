<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\AssignRolePermissionsRequest;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\Permission\PermissionResource;
use App\Http\Resources\Role\RoleResource;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(): JsonResponse
    {
        $roles = Role::query()
            ->with('permissions')
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Roles retrieved successfully.',
            'data' => RoleResource::collection($roles),
            'status_code' => 200,
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request): JsonResponse {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        $role->load('permissions');

        return response()->json([
            'status' => true,
            'message' => 'Role created successfully.',
            'data' => [
                'role' => new RoleResource($role),
            ],
            'status_code' => 201,
        ], 201);
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): JsonResponse
    {
        abort_unless(
            $role->guard_name === 'web',
            404
        );

        $role->load('permissions');

        return response()->json([
            'status' => true,
            'message' => 'Role retrieved successfully.',
            'data' => [
                'role' => new RoleResource($role),
            ],
            'status_code' => 200,
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse {
        abort_unless(
            $role->guard_name === 'web',
            404
        );

        $role->update([
            'name' => $request->validated('name'),
        ]);

        $role->load('permissions');

        return response()->json([
            'status' => true,
            'message' => 'Role updated successfully.',
            'data' => [
                'role' => new RoleResource($role),
            ],
            'status_code' => 200,
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): JsonResponse
    {
        abort_unless(
            $role->guard_name === 'web',
            404
        );

        $role->delete();

        return response()->json([
            'status' => true,
            'message' => 'Role deleted successfully.',
            'data' => null,
            'status_code' => 200,
        ]);
    }

    public function permissions(): JsonResponse {
        $permission = Permission::query()
        ->where('guard_name', 'web')->orderBy('name')->get();

        return response()->json([
            'status' => true,
            'message' => 'Permissions retrieved successfully.',
            'data' => PermissionResource::collection($permission),
            'status_code' => 200,
        ]);
    }

    public function assignPermission(AssignRolePermissionsRequest $request, Role $role): JsonResponse {
        abort_unless($role->guard_name === 'web', 404);

        $role->syncPermissions($request->validated('permission'));
         
        return response()->json([
            'status' => true,
            'message' => 'Role permissions updated successfully.',
            'data' => [ 
                'role' => new RoleResource($role),
            ],
            'status_code' => 200,
        ]);
    }
}