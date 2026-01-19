<?php

namespace App\Payments;

use App\Models\Payment;
use App\Payments\Gateways\AbnAmroGateway;
use App\Payments\Gateways\IdealGateway;
use App\Payments\Gateways\IngGateway;
use App\Payments\Gateways\MollieGateway;
use App\Payments\Gateways\PaymentGatewayInterface;

class GatewayResolver
{
    public function resolve(Payment $payment): PaymentGatewayInterface
    {
        return match ($payment->gateway) {
            'mollie' => new MollieGateway,
            'abn-amro' => new AbnAmroGateway,
            'ing' => new IngGateway,
            default => new IdealGateway,
        };
    }
}
