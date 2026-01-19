<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use Mollie\Api\MollieApiClient;

class MollieGateway implements PaymentGatewayInterface
{
    public function charge(Payment $payment): GatewayResult
    {
        $mollie = new MollieApiClient();
        $mollie->setApiKey(config('services.mollie.key'));

        $baseUrl = config('app.public_url');
        $molliePayment = $mollie->payments->create([
            'amount' => [
                'currency' => 'EUR',
                'value' => number_format($payment->amount / 100, 2, '.', ''),
            ],
            'description' => 'Payment #' . $payment->id,
            'redirectUrl' => $baseUrl . '/payment/return/' . $payment->id,
            'webhookUrl'  => $baseUrl . '/api/webhooks/mollie',
            'method' => 'ideal',
            'metadata' => [
                'payment_id' => $payment->id,
            ],
        ]);

        $payment->provider = 'mollie';
        $payment->provider_payment_id = $molliePayment->id;
        $payment->save();

        return new GatewayResult(
            success: true,
            checkoutUrl: $molliePayment->getCheckoutUrl(),
            async: true
        );
    }
}

