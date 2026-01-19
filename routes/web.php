<?php

use App\Http\Controllers\PaymentReturnController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pay', function () {
    return view('pay');
});

Route::get('/payment/return/{payment}', [PaymentReturnController::class, 'show'])
    ->name('payments.return');
