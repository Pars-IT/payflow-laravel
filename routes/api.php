<?php

use App\Http\Controllers\MollieWebhookController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::post('/payments', [PaymentController::class, 'store']);
Route::get('/gateways', [PaymentController::class, 'gateways'])
    ->middleware('cache.headers:public;max_age=60;etag');
Route::get('/payments/{id}', [PaymentController::class, 'show']);

Route::post('/webhooks/mollie', [MollieWebhookController::class, 'handle'])
    ->name('webhooks.mollie')
    ->middleware('throttle:10,1');

Route::get('/health', fn () => response()->json([
    'ok' => true,
    'timestamp' => now(),
]));

Route::get('/wallets/{userId}', [WalletController::class, 'show']);
