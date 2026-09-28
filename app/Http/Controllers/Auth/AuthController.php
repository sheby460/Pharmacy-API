<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\User\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Return the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'roles.permissions',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Authenticated user retrieved successfully.',
            'data' => [
                'user' => new UserResource($user),
            ],
        ], 200);
    }

    /**
     * Login.
     */
    public function login(
        LoginRequest $request,
        AuthService $service
    ): JsonResponse {
        $result = $service->login($request->validated());

        return response()->json(
            $result,
            $result['status_code']
        );
    }

    /**
     * Register.
     */
    public function register(
        RegisterRequest $request,
        AuthService $service
    ): JsonResponse {
        $result = $service->register($request->validated());

        return response()->json(
            $result,
            $result['status_code']
        );
    }

    /**
     * Logout from the current device/token.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully.',
            'data' => null,
        ], 200);
    }

    /**
     * Logout from all devices.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logged out from all devices successfully.',
            'data' => null,
        ], 200);
    }
}
