<?php

use App\Http\Controllers\Webhooks\WooCommerce\OrderController;
use App\Http\Controllers\Webhooks\WooCommerce\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('woocommerce.webhook')->prefix('webhooks/woocommerce')->name('webhooks.woocommerce.')->group(function () {
    Route::post('orders', OrderController::class)->name('orders');
    Route::post('products', ProductController::class)->name('products');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
})->name('user');
