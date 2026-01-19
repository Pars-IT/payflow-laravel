<?php

use App\Http\Controllers\MollieWebhookController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::post('/payments', [PaymentController::class, 'store']);
Route::get('/payments/{id}', [PaymentController::class, 'show']);

Route::post('/webhooks/mollie', [MollieWebhookController::class, 'handle'])
    ->name('webhooks.mollie');
