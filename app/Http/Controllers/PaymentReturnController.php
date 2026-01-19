<?php

namespace App\Http\Controllers;

use App\Models\Payment;

class PaymentReturnController extends Controller
{
    public function show(Payment $payment)
    {
        return view('payment-return', [
            'payment' => $payment,
        ]);
    }
}
