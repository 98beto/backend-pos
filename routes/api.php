<?php

use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CashMovementController;
use App\Http\Controllers\Api\CashSessionController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InventoryMovementController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SaleDetailController;
use App\Http\Controllers\Api\SavedCartController;
use App\Http\Controllers\Api\SupplierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Dashboard
Route::get('dashboard', [DashboardController::class, 'summary']);

// Brands
Route::apiResource('brands', BrandController::class);

// Branches
Route::apiResource('branches', BranchController::class)->only(['index', 'store', 'show', 'update']);

// Categories
Route::apiResource('categories', CategoryController::class);

// Products
Route::get('products/low-stock', [ProductController::class, 'lowStock']);
Route::apiResource('products', ProductController::class);

// Customers
Route::apiResource('customers', CustomerController::class);

// Suppliers
Route::apiResource('suppliers', SupplierController::class);

// Sales
Route::apiResource('sales', SaleController::class)->only(['index', 'store', 'show']);

// Saved Carts
Route::patch('saved-carts/{savedCart}/recover', [SavedCartController::class, 'recover']);
Route::apiResource('saved-carts', SavedCartController::class);

// Sale Details (read-only; se crean junto con la venta)
Route::apiResource('sale-details', SaleDetailController::class)->only(['index', 'show']);

// Cash Sessions
Route::prefix('cash-sessions')->group(function () {
    Route::get('/', [CashSessionController::class, 'index']);
    Route::post('/open', [CashSessionController::class, 'open']);
    Route::get('/current', [CashSessionController::class, 'current']);
    Route::get('/{cashSession}/movements', [CashMovementController::class, 'index']);
    Route::post('/{cashSession}/movements', [CashMovementController::class, 'store']);
    Route::post('/{cashSession}/close', [CashSessionController::class, 'close']);
    Route::get('/{cashSession}', [CashSessionController::class, 'show']);
});

// Inventory Movements
Route::apiResource('inventory/movements', InventoryMovementController::class)
    ->only(['index', 'store', 'show']);
