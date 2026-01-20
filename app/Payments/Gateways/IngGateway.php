<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\GatewayResult;
use App\Payments\PaymentGatewayInterface;

class IngGateway implements PaymentGatewayInterface
{
    public function charge(Payment $payment): GatewayResult
    {
        // Test: Always successful
        return GatewayResult::success();
    }
}
