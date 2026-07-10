<?php

use App\Http\Controllers\ProductController;
use App\Http\Middleware\InferShopFromSession;
use Illuminate\Support\Facades\Route;

// Note: '/' is intentionally not defined here. The kyon147/laravel-shopify
// package registers its own authenticated home route at '/' (name: "home"),
// and a route defined here would silently take priority over it and skip
// shop authentication entirely.

Route::get('/products', [ProductController::class, 'index'])
    ->middleware([InferShopFromSession::class, 'verify.shopify'])
    ->name('products.index');
