<?php

namespace App\Payments\Gateways;

use App\Exceptions\Payments\PspException;
use App\Models\Payment;
use App\Payments\GatewayResult;
use App\Payments\PaymentGatewayInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;

class MollieGateway implements PaymentGatewayInterface
{
    public function charge(Payment $payment): GatewayResult
    {
        try {
            $mollie = new MollieApiClient;
            $mollie->setApiKey(config('services.mollie.key'));

            $baseUrl = config('app.url');
            $molliePayment = $mollie->payments->create([
                'amount' => [
                    'currency' => 'EUR',
                    'value' => number_format($payment->amount / 100, 2, '.', ''),
                ],
                'description' => 'Payment #'.$payment->id,
                'redirectUrl' => $baseUrl.'/payment/return/'.$payment->id,
                'webhookUrl' => $baseUrl.'/api/webhooks/mollie',
                'method' => 'ideal',
                'metadata' => [
                    'payment_id' => $payment->id,
                ],
            ]);

            $payment->provider = 'mollie';
            $payment->provider_payment_id = $molliePayment->id;
            $payment->provider_checkout_url = $molliePayment->getCheckoutUrl();
            $payment->save();

            return GatewayResult::async(
                $molliePayment->getCheckoutUrl()
            );
        } catch (ApiException $e) {
            // Log full Mollie error (for debugging / ops)
            logger()->error('Mollie API error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $reason = match (true) {
                str_contains($e->getMessage(), 'webhook') => 'psp_webhook_unreachable',
                str_contains($e->getMessage(), 'amount') => 'psp_invalid_amount',
                default => 'psp_error',
            };

            throw new PspException($reason);
        }
    }
}
