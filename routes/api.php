<?php

use App\Http\Controllers\API\BranchController;
use App\Http\Controllers\API\EntityController;
use App\Http\Controllers\API\ItemController;
use App\Http\Controllers\API\StockController;
use App\Http\Controllers\API\StoreController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;





// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post("moduleSetup", [EntityController::class, "moduleSetup"]);

Route::apiResource('entities', App\Http\Controllers\API\EntityController::class);
Route::apiResource('branches', App\Http\Controllers\API\BranchController::class);
Route::apiResource('items', App\Http\Controllers\API\ItemController::class);
Route::apiResource('stores', App\Http\Controllers\API\StoreController::class);
Route::apiResource('stock', App\Http\Controllers\API\StockController::class);
Route::apiResource('stock-transfers', App\Http\Controllers\API\StockTransferController::class);
Route::apiResource('suppliers', App\Http\Controllers\API\SupplierController::class);
Route::apiResource('orders', App\Http\Controllers\API\OrderController::class);

Route::get('/branches/external/{external_id}', [BranchController::class, 'getBranchesByExternalId']);

Route::get("getStoresByEntityId/{entityId}", [StoreController::class, "getStoresByEntityId"]);
Route::get("getStoresByBranchId/{branchId}", [StoreController::class, "getStoresByBranchId"]);
Route::get("getStoreByExternalId/{externalId}", [StoreController::class, "getStoreByExternalId"]);


//items
Route::get("items/external/{externalId}", [App\Http\Controllers\API\ItemController::class, "getItemsByExternalId"]);
Route::get("getItemsByExternalId/{externalId}", [ItemController::class, "getItemsByExternalId"]);

//getStockByExternalId
Route::get('getStockByExternalId/{externalId}', [StockController::class, 'getStockByExternalId']);


// Route::get("getBranchesByExternalId/")

