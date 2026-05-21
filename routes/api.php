<?php

use App\Data\Users\UserData;
use App\Http\Controllers\Orders\CustomOrderController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Users\UserInvitationController;
use App\Http\Controllers\Webhooks\WooCommerce\OrderController as WebhookOrderController;
use App\Http\Controllers\Webhooks\WooCommerce\ProductController as WebhookProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('woocommerce.webhook')->prefix('webhooks/woocommerce')->name('webhooks.woocommerce.')->group(function () {
    Route::post('orders', WebhookOrderController::class)->name('orders');
    Route::post('products', WebhookProductController::class)->name('products');
});

// Public invitation flow — the invitee follows a link from their email
// and does not have a session yet.
Route::prefix('invitations')->name('invitations.')->group(function () {
    Route::get('/{invitation}', [UserInvitationController::class, 'show'])->name('show');
    Route::post('/{invitation}', [UserInvitationController::class, 'accept'])->name('accept');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => UserData::from($request->user()))->name('user');

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

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/permissions', [UserController::class, 'permissions'])->name('permissions');
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::patch('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');

        Route::post('/{user}/deactivate', [UserController::class, 'deactivate'])->name('deactivate');
        Route::post('/{user}/reactivate', [UserController::class, 'reactivate'])->name('reactivate');
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        Route::post('/{user}/resend-invitation', [UserController::class, 'resendInvitation'])->name('resend-invitation');
    });
});
