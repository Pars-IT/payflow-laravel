<?php

namespace App\Payments\Gateways;

use App\Models\Payment;

class IngGateway implements PaymentGatewayInterface
{
    public function charge(Payment $payment): GatewayResult
    {
        // Test: Always successful
        return new GatewayResult(true);
    }
}
