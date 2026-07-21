<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseStockController;
use App\Http\Controllers\StoreStockController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\ExpiredProductController;

/*
|--------------------------------------------------------------------------
| PUBLIC
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// TEST HEADER 
Route::get('/test-header', function (\Illuminate\Http\Request $request) {
        return response()->json([
            'authorization' => $request->header('Authorization'),
            'bearer_token' => $request->bearerToken(),
        ]);
});    

/*
|--------------------------------------------------------------------------
| PROTECTED (LOGIN REQUIRED)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------
    | AUTH USER
    |--------------------------
    */
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------
    | DASHBOARD
    |--------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:admin,kasir');

    /*
    |--------------------------
    | SALES (POS SYSTEM)
    |--------------------------
    */

    Route::get('sales/report', [SaleController::class, 'report'])
        ->middleware('role:admin');

    Route::get('sales/export/excel', [SaleController::class, 'exportExcel'])
        ->middleware('role:admin');
    /*
    |--------------------------
    | SALES (POS SYSTEM)
    |--------------------------
    */
    Route::apiResource('sales', SaleController::class)
        ->middleware('role:admin,kasir');

    Route::get('/sales/{sale}/invoice', [SaleController::class, 'invoice'])
        ->middleware('role:admin,kasir');

    Route::get('/sales/{sale}/receipt', [SaleController::class, 'receipt'])
        ->middleware('role:admin,kasir');

    /*
    |--------------------------
    | ADMIN ONLY
    |--------------------------
    */
    Route::apiResource('categories', CategoryController::class)
        ->middleware('role:admin');

    Route::apiResource('products', ProductController::class)
        ->middleware('role:admin');

    Route::apiResource('warehouse-stocks', WarehouseStockController::class)
        ->middleware('role:admin');

    Route::apiResource('store-stocks', StoreStockController::class)
    ->middleware('role:admin,kasir');

    Route::post('stock-transfer', [StockTransferController::class, 'transfer'])
        ->middleware('role:admin');

    Route::get('stock-transfer/history', [StockTransferController::class, 'history'])
        ->middleware('role:admin');

    Route::get('low-stock', [AlertController::class, 'lowStock'])
        ->middleware('role:admin');

    Route::get('expired-products', [ExpiredProductController::class, 'index'])
        ->middleware('role:admin');
       
});