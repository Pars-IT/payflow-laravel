<?php

namespace App\Payments;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    public function charge(Payment $payment): GatewayResult;
}
