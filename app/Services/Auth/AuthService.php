<?php
namespace App\Services\Auth;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\AuthResource\UserResource;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    public function register($data)
    {
        $data['password'] = Hash::make($data['password']);

        $user = $this->userRepository->create($data);

        return [
            'status' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => new UserResource($user),
                'token' => $user->createToken('auth_token')->plainTextToken
            ]
        ];
    }

    public function login($data)
    {
        if (!Auth::attempt($data)) {
            return [
                'status' => false,
                'message' => 'Invalid credentials'
            ];
        }

        $user = Auth::user();

        return [
            'status' => true,
            'message' => 'User logged in successfully',
            'data' => [
                'user' => new UserResource($user),
                'token' => $user->createToken('auth_token')->plainTextToken
            ]
        ];
    }
}