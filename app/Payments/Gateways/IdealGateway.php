<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\GatewayResult;
use App\Payments\PaymentGatewayInterface;

class IdealGateway implements PaymentGatewayInterface
{
    public function charge(Payment $payment): GatewayResult
    {
        if ($payment->amount < 1000) {
            return GatewayResult::failed('amount_too_low');
        }

        return GatewayResult::success();
    }
}
