<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AssignRoleRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UpdateUserStatusRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with([
            'roles.permissions',
        ]);

        if ($request->filled('search')) {
            $search = $request->string('search');

            $query->where(function ($q) use ($search) {
                $q->where('fname', 'like', "%{$search}%")
                    ->orWhere('lname', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where(
                'is_active',
                $request->boolean('is_active')
            );
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where(
                    'name',
                    $request->string('role')
                );
            });
        }

        $users = $query
            ->latest()
            ->paginate(
                $request->integer('per_page', 15)
            );

        return response()->json([
            'status' => true,
            'message' => 'Users retrieved successfully.',
            'data' => UserResource::collection(
                $users->items()
            ),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            'status_code' => 200,
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create(
            $request->validated()
        );

        $user->load([
            'roles.permissions',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User created successfully.',
            'data' => [
                'user' => new UserResource($user),
            ],
            'status_code' => 201,
        ], 201);
    }

    public function show(User $user)
    {
        $user->load([
            'roles.permissions',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User retrieved successfully.',
            'data' => [
                'user' => new UserResource($user),
            ],
            'status_code' => 200,
        ]);
    }

    public function update(
        UpdateUserRequest $request,
        User $user
    ) {
        $user->update(
            $request->validated()
        );

        $user->load([
            'roles.permissions',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User updated successfully.',
            'data' => [
                'user' => new UserResource($user),
            ],
            'status_code' => 200,
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot delete your own account.',
                'data' => null,
                'status_code' => 422,
            ], 422);
        }

        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'User deleted successfully.',
            'data' => null,
            'status_code' => 200,
        ]);
    }

    public function updateStatus(
        UpdateUserStatusRequest $request,
        User $user
    ) {
        if ($user->id === auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot change your own account status.',
                'data' => null,
                'status_code' => 422,
            ], 422);
        }

        $user->update([
            'is_active' => $request->boolean(
                'is_active'
            ),
        ]);

        $user->load([
            'roles.permissions',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User status updated successfully.',
            'data' => [
                'user' => new UserResource($user),
            ],
            'status_code' => 200,
        ]);
    }
    public function assignRole(AssignRoleRequest $request, User $user){
        $user->syncRoles($request->validated('roles'));

        $user->load([
            'roles.permissions',
        ]);

        return response()->json([
        'status' => true,
        'message' => 'User roles updated successfully.',
        'data' => [
            'user' => new UserResource($user),
        ],
        'status_code' => 200, 
        ]);
    }
}