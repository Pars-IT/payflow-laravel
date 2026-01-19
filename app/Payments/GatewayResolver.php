<?php

namespace App\Payments;

use App\Models\Payment;
use App\Payments\Gateways\{
    AbnAmroGateway,
    PaymentGatewayInterface,
    IdealGateway,
    IngGateway,
    MollieGateway
};

class GatewayResolver
{
    public function resolve(Payment $payment): PaymentGatewayInterface
    {
        return match ($payment->gateway) {
            'mollie'   => new MollieGateway(),
            'abn-amro' => new AbnAmroGateway(),
            'ing'      => new IngGateway(),
            default    => new IdealGateway(),
        };
    }
}
