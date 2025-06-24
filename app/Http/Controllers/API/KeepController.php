<?php

use App\Http\Controllers\StockManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/stock-management/create', [StockManagementController::class, 'create'])->name('stock-management.create');
Route::post('/stock-management/stock-count', [StockManagementController::class, 'storeStockCount'])->name('stock-management.stock-count.store');

// New routes for Make an Order, Bulk Ordering, and Bulk Stock Counting
Route::post('/stock-management/order', [StockManagementController::class, 'storeOrder'])->name('stock-management.order.store');
Route::post('/stock-management/bulk-order', [StockManagementController::class, 'storeBulkOrder'])->name('stock-management.bulk-order.store');
Route::post('/stock-management/bulk-stock-count', [StockManagementController::class, 'storeBulkStockCount'])->name('stock-management.bulk-stock-count.store');