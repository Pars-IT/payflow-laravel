<?php

namespace App\Payments\Gateways;

use App\Exceptions\Payments\PspException;
use App\Models\Payment;
use App\Payments\GatewayResult;
use App\Payments\PaymentGatewayInterface;

class IdealGateway implements PaymentGatewayInterface
{
    public function charge(Payment $payment): GatewayResult
    {
        if ($payment->amount < 1000) {
            throw new PspException('amount_too_low');
        }

        return new GatewayResult;
    }
}
