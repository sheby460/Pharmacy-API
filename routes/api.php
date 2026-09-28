<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Supplier\SupplierController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\SubCategory\SubCategoryController;
use App\Http\Controllers\Drugs\DrugsController;
use App\Http\Controllers\DrugBatches\DrugBatchController;
use App\Http\Controllers\StockMovements\StockMovementController;
use App\Http\Controllers\Purchase\PurchaseController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\Sales\SaleController;

// ─────────────────────────────────────────────
// Public Auth Routes
// ─────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/register', [AuthController::class, 'register']);
});

// ─────────────────────────────────────────────
// Protected Routes (require auth:sanctum)
// ─────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    });

    // Users
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create');
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware('permission:users.view');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete');
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->middleware('permission:users.update');
    Route::put('/users/{user}/role', [UserController::class, 'assignRole'])->middleware('permission:users.assign-roles');

    Route::middleware('permission:roles.view')->get('/roles', [RoleController::class, 'index']);
    Route::middleware('permission:roles.create')->post('/roles', [RoleController::class, 'store']);
    Route::middleware('permission:roles.view')->get('/roles/{role}', [RoleController::class, 'show']);
    Route::middleware('permission:roles.update')->put('/roles/{role}', [RoleController::class, 'update']);
    Route::middleware('permission:roles.delete')->delete('/roles/{role}', [RoleController::class, 'destroy']);

    Route::middleware('permission:roles.view')->get('/permissions', [RoleController::class, 'permissions']);
    Route::middleware('permission:roles.update')->put('/roles/{role}/permissions', [RoleController::class, 'assignPermissions']);
    // Suppliers
    Route::get('/suppliers', [SupplierController::class, 'index'])->middleware('permission:suppliers.view');
    Route::post('/suppliers', [SupplierController::class, 'store'])->middleware('permission:suppliers.create');
    Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->middleware('permission:suppliers.view');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('permission:suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('permission:suppliers.delete');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->middleware('permission:customers.view');
    Route::post('/customers', [CustomerController::class, 'store'])->middleware('permission:customers.create');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware('permission:customers.view');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->middleware('permission:customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->middleware('permission:customers.delete');

    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->middleware('permission:categories.view');
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('permission:categories.create');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->middleware('permission:categories.view');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('permission:categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.delete');

    // SubCategories  (note the capital C to match your frontend)
    Route::get('/subCategories', [SubCategoryController::class, 'index'])->middleware('permission:subCategories.view');
    Route::post('/subCategories', [SubCategoryController::class, 'store'])->middleware('permission:subCategories.create');
    Route::get('/subCategories/{subCategory}', [SubCategoryController::class, 'show'])->middleware('permission:subCategories.view');
    Route::put('/subCategories/{subCategory}', [SubCategoryController::class, 'update'])->middleware('permission:subCategories.update');
    Route::delete('/subCategories/{subCategory}', [SubCategoryController::class, 'destroy'])->middleware('permission:subCategories.delete');

    // Drugs
    Route::get('/drugs', [DrugsController::class, 'index'])->middleware('permission:drugs.view');
    Route::post('/drugs', [DrugsController::class, 'store'])->middleware('permission:drugs.create');
    Route::get('/drugs/{drug}', [DrugsController::class, 'show'])->middleware('permission:drugs.view');
    Route::put('/drugs/{drug}', [DrugsController::class, 'update'])->middleware('permission:drugs.update');
    Route::delete('/drugs/{drug}', [DrugsController::class, 'destroy'])->middleware('permission:drugs.delete');

    // Drug Batches
    Route::get('/drug-batches', [DrugBatchController::class, 'index'])->middleware('permission:drug-batches.view');
    Route::post('/drug-batches', [DrugBatchController::class, 'store'])->middleware('permission:drug-batches.create');
    Route::get('/drug-batches/{drugBatch}', [DrugBatchController::class, 'show'])->middleware('permission:drug-batches.view');
    Route::put('/drug-batches/{drugBatch}', [DrugBatchController::class, 'update'])->middleware('permission:drug-batches.update');
    Route::delete('/drug-batches/{drugBatch}', [DrugBatchController::class, 'destroy'])->middleware('permission:drug-batches.delete');

    // Stock Movements
    Route::get('/stock-movements', [StockMovementController::class, 'index'])->middleware('permission:inventory.view-movements');
    Route::post('/stock-movements', [StockMovementController::class, 'store'])->middleware('permission:inventory.adjust-stock');

    // Purchases
    Route::get('/purchases', [PurchaseController::class, 'index'])->middleware('permission:purchases.view');
    Route::post('/purchases', [PurchaseController::class, 'store'])->middleware('permission:purchases.create');
    Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->middleware('permission:purchases.view');
    Route::put('/purchases/{purchase}', [PurchaseController::class, 'update'])->middleware('permission:purchases.update');
    Route::post('/purchases/{purchase}/approve', [PurchaseController::class, 'approve'])->middleware('permission:purchases.approve');
    Route::post('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->middleware('permission:purchases.cancel');

    // Sales
    Route::get('/sales', [SaleController::class, 'index'])->middleware('permission:sales.view');
    Route::post('/sales', [SaleController::class, 'store'])->middleware('permission:sales.create');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->middleware('permission:sales.view');
    Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->middleware('permission:sales.cancel');
    Route::post('/sales/{sale}/refund', [SaleController::class, 'refund'])->middleware('permission:sales.refund');
});
