<?php

use App\Http\Controllers\MollieWebhookController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/payments', [PaymentController::class, 'store']);
Route::get('/payments/{id}', [PaymentController::class, 'show']);

Route::post('/webhooks/mollie', [MollieWebhookController::class, 'handle'])
    ->name('webhooks.mollie');
