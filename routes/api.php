<?php

use App\Http\Controllers\Api\Webhooks\WooCommerceOrderController;
use App\Http\Controllers\Api\Webhooks\WooCommerceProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('woocommerce.webhook')->prefix('webhooks/woocommerce')->name('webhooks.woocommerce.')->group(function () {
    Route::post('orders', WooCommerceOrderController::class)->name('orders');
    Route::post('products', WooCommerceProductController::class)->name('products');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
})->name('user');
