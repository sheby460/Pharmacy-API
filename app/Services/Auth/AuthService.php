<?php

namespace App\Services\Auth;

use App\Http\Resources\AuthResource\UserResource;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * Register a new user.
     *
     * The User model's "hashed" cast handles password hashing.
     */
    public function register(array $data): array
    {
        try {
            $user = DB::transaction(function () use ($data) {
                return $this->userRepository->create($data);
            });

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'status' => true,
                'message' => 'User registered successfully.',
                'data' => [
                    'user' => new UserResource($user),
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
                'status_code' => 201,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'status' => false,
                'message' => 'Unable to register the user.',
                'data' => null,
                'status_code' => 500,
            ];
        }
    }

    /**
     * Authenticate a user using username and password.
     */
    public function login(array $data): array
    {
        $credentials = [
            'username' => $data['username'],
            'password' => $data['password'],
        ];

        if (!Auth::guard('web')->attempt($credentials)) {
            return [
                'status' => false,
                'message' => 'Invalid username or password.',
                'data' => null,
                'status_code' => 401,
            ];
        }

        $user = Auth::guard('web')->user();

        /*
        |--------------------------------------------------------------------------
        | Optional token policy
        |--------------------------------------------------------------------------
        |
        | We keep existing tokens so users can stay logged in on multiple
        | approved devices. Logout endpoints handle token revocation.
        |
        */

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'status' => true,
            'message' => 'User logged in successfully.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
            'status_code' => 200,
        ];
    }
}