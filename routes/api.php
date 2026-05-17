<?php

use App\Http\Controllers\CustomOrderController;
use App\Http\Controllers\Webhooks\WooCommerce\OrderController;
use App\Http\Controllers\Webhooks\WooCommerce\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('woocommerce.webhook')->prefix('webhooks/woocommerce')->name('webhooks.woocommerce.')->group(function () {
    Route::post('orders', OrderController::class)->name('orders');
    Route::post('products', ProductController::class)->name('products');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user())->name('user');

    Route::prefix('custom-orders')->name('custom-orders.')->group(function () {
        Route::get('/', [CustomOrderController::class, 'list'])->name('list');

        Route::prefix('{order}/submission')->name('submission.')->group(function () {
            Route::get('/', [CustomOrderController::class, 'submission'])->name('show');
            Route::patch('/', [CustomOrderController::class, 'update'])->name('update');
            Route::post('/send', [CustomOrderController::class, 'send'])->name('send');
        });
    });
});
