<?php

use App\Http\Controllers\Api\CatalogLayoutController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('catalog-layout', [CatalogLayoutController::class, 'show'])
    ->name('api.catalog-layout.show');

Route::post('orders', [OrderController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('api.orders.store');
