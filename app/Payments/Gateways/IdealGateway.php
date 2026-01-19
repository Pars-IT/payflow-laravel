<?php

namespace App\Payments\Gateways;

use App\Models\Payment;

class IdealGateway implements PaymentGatewayInterface
{
    public function charge(Payment $payment): GatewayResult
    {
        if ($payment->amount < 1000) {
            return new GatewayResult(false, 'amount_too_low');
        }

        return new GatewayResult(true);
    }
}
