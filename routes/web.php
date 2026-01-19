<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentReturnController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pay', function () {
    return view('pay');
});

Route::get('/payment/return/{payment}', [PaymentReturnController::class, 'show'])
    ->name('payments.return');