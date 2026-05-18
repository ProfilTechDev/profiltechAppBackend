<?php

use App\Http\Controllers\Orders\CustomOrderController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Webhooks\WooCommerce\OrderController as WebhookOrderController;
use App\Http\Controllers\Webhooks\WooCommerce\ProductController as WebhookProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('woocommerce.webhook')->prefix('webhooks/woocommerce')->name('webhooks.woocommerce.')->group(function () {
    Route::post('orders', WebhookOrderController::class)->name('orders');
    Route::post('products', WebhookProductController::class)->name('products');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user())->name('user');

    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/colors', [ProductController::class, 'colors'])->name('colors');
    });

    Route::prefix('custom-orders')->name('custom-orders.')->group(function () {
        Route::get('/', [CustomOrderController::class, 'list'])->name('list');
        Route::get('/providers', [CustomOrderController::class, 'providers'])->name('providers');

        Route::prefix('{order}/submission')->name('submission.')->group(function () {
            Route::get('/', [CustomOrderController::class, 'submission'])->name('show');
            Route::patch('/', [CustomOrderController::class, 'update'])->name('update');
            Route::post('/send', [CustomOrderController::class, 'send'])->name('send');
        });
    });
});
