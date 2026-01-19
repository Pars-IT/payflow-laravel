<?php

namespace App\Payments;

use App\Models\Payment;
use App\Payments\Gateways\{
    AbnAmroGateway,
    PaymentGatewayInterface,
    IdealGateway,
    IngGateway
};

class GatewayResolver
{
    public function resolve(Payment $payment): PaymentGatewayInterface
    {
        return match ($payment->gateway) {
            'abn-amro' => new AbnAmroGateway(),
            'ing'      => new IngGateway(),
            default    => new IdealGateway(),
        };
    }
}
