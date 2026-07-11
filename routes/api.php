<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Supplier\SupplierController;
use App\Http\Controllers\Customer\CustomerController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group([], function() {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

 Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index']);      
        Route::post('/', [SupplierController::class, 'store']);     
        Route::get('/{id}', [SupplierController::class, 'show']);   
        Route::put('/{id}', [SupplierController::class, 'update']); 
        Route::delete('/{id}', [SupplierController::class, 'destroy']);
    });

   Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index']);      
        Route::post('/', [CustomerController::class, 'store']);     
        Route::get('/{id}', [CustomerController::class, 'show']);   
        Route::put('/{id}', [CustomerController::class, 'update']); 
        Route::delete('/{id}', [CustomerController::class, 'destroy']);
    });


