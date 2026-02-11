<?php

use App\Http\Controllers\MollieWebhookController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::post('/payments', [PaymentController::class, 'store']);
Route::get('/payments/{id}', [PaymentController::class, 'show']);

Route::post('/webhooks/mollie', [MollieWebhookController::class, 'handle'])
    ->name('webhooks.mollie');

Route::get('/health', fn () => response()->json(['ok' => true]));

Route::get('/wallets/{userId}/credit', [WalletController::class, 'showCredit']);
