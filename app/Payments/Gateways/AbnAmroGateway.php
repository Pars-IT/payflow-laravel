<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\GatewayResult;
use App\Payments\PaymentGatewayInterface;

class AbnAmroGateway implements PaymentGatewayInterface
{
    public function charge(Payment $payment): GatewayResult
    {
        // Test: only even sums
        if ($payment->amount % 2 !== 0) {
            return GatewayResult::failed('abn_amro_rejected');
        }

        return GatewayResult::success();
    }
}
