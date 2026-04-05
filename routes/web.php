<?php

use App\Models\Payment;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pay', function () {
    return view('payments.pay');
})->middleware('throttle:20,1');

Route::get('/payments/{payment}', function (Payment $payment) {
    return view('payments.status', [
        'paymentId' => $payment->id,
    ]);
});
