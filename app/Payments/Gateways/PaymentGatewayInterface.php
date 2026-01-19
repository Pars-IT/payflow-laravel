<?php

namespace App\Payments\Gateways;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    public function charge(Payment $payment): GatewayResult;
}
