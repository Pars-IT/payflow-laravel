<?php

namespace App\Payments\Gateways;

use App\Models\Payment;

class AbnAmroGateway implements PaymentGatewayInterface
{
    public function charge(Payment $payment): GatewayResult
    {
        // Test: only even sums
        if ($payment->amount % 2 !== 0) {
            return new GatewayResult(false, 'odd_amount_not_supported');
        }

        return new GatewayResult(true);
    }
}
