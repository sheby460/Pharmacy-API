<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller; 
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;

class AuthController extends Controller
{
   public function me()
   {
      return response()->json(auth()->user());
   }


   public function login(LoginRequest $request, AuthService $service)
   { 
      return response()->json(
         $service->login($request->validated())
      );
   }

   public function register(RegisterRequest $request, AuthService $service)
   {
      return response()->json(
         $service->register($request->validated()),
         201
      );
   }

   public function logout()
   {
      auth()->user()->tokens()->delete();

      return response()->json([
         'status' => true,
         'message' => 'User logged out successfully',
      ]);
   }
}
