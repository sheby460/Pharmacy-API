<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Supplier\SupplierController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group([], function() {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

 Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index']);      // GET /api/suppliers
        Route::post('/', [SupplierController::class, 'store']);     // POST /api/suppliers
        Route::get('/{id}', [SupplierController::class, 'show']);   // GET /api/suppliers/{id}
        Route::put('/{id}', [SupplierController::class, 'update']); // PUT /api/suppliers/{id}
        Route::delete('/{id}', [SupplierController::class, 'destroy']); // DELETE /api/suppliers/{id}
    });

