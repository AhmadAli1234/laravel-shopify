<?php

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SyncStatusController;
use App\Http\Middleware\InferShopFromSession;
use Illuminate\Support\Facades\Route;

// Note: '/' is intentionally not defined here. The kyon147/laravel-shopify
// package registers its own authenticated home route at '/' (name: "home"),
// and a route defined here would silently take priority over it and skip
// shop authentication entirely.

// Deliberately outside the verify.shopify group below: this page must be
// reachable even with zero Shopify credentials configured (that's the whole
// point - see EnsureShopifyConfigured), and it isn't a per-shop embedded
// page at all, just a plain admin settings screen for this deployment.
// Protected by the 'admin' guard (Fortify) - see EnsureShopifyConfigured
// for why /login itself must stay reachable even when unconfigured.
Route::middleware('auth:admin')->group(function () {
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

Route::middleware([InferShopFromSession::class, 'verify.shopify'])->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');

    Route::get('/collections', [CollectionController::class, 'index'])->name('collections.index');

    Route::get('/discounts', [DiscountController::class, 'index'])->name('discounts.index');

    Route::get('/sync-status', SyncStatusController::class)->name('sync-status');
});
