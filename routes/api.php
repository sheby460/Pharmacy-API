<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Supplier\SupplierController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\SubCategory\SubCategoryController;
use App\Http\Controllers\Drugs\DrugsController;
use App\Http\Controllers\DrugBatches\DrugBatchController;
use App\Http\Controllers\Purchase\PurchaseController;
use App\Http\Controllers\StockMovements\StockMovementController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group([], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
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

Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::post('/', [CategoryController::class, 'store']);
    Route::get('/{id}', [CategoryController::class, 'show']);
    Route::put('/{id}', [CategoryController::class, 'update']);
    Route::delete('/{id}', [CategoryController::class, 'destroy']);
});

Route::prefix('subCategories')->group(function () {
    Route::get('/', [SubCategoryController::class, 'index']);
    Route::post('/', [SubCategoryController::class, 'store']);
    Route::get('/{id}', [SubCategoryController::class, 'show']);
    Route::put('/{id}', [SubCategoryController::class, 'update']);
    Route::delete('/{id}', [SubCategoryController::class, 'destroy']);
});

Route::prefix('drugs')->group(function () {
    Route::get('/', [DrugsController::class, 'index']);
    Route::post('/', [DrugsController::class, 'store']);
    Route::get('/{id}', [DrugsController::class, 'show']);
    Route::put('/{id}', [DrugsController::class, 'update']);
    Route::delete('/{id}', [DrugsController::class, 'destroy']);
});

Route::prefix('drug-batches')->group(function () {
    Route::get('/', [DrugBatchController::class, 'index']);
    Route::post('/', [DrugBatchController::class, 'store']);
    Route::get('/{id}', [DrugBatchController::class, 'show'])->whereNumber('id');
    Route::get('/drugs/{drugId}/batches', [ DrugBatchController::class, 'byDrug'])->whereNumber('drugId');
});

Route::prefix('stock-movements')->group(function () {
    Route::get('/', [StockMovementController::class, 'index']);
    Route::get('/{id}', [StockMovementController::class, 'show'])->whereNumber('id');
    Route::post('/adjust', [StockMovementController::class, 'adjust']);
});

Route::prefix('purchases')->group(function () {
    Route::get('/', [PurchaseController::class, 'index']);
    Route::post('/', [PurchaseController::class, 'store']);
    Route::get('/{id}', [PurchaseController::class, 'show'])->whereNumber('id');
    Route::post('/{id}/receive', [PurchaseController::class, 'receive'])->whereNumber('id');
});

