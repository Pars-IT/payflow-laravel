<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Events\PaymentFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;

class SendPaymentWebhookListener implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 10;

    public function handle(PaymentSucceeded|PaymentFailed $event): void
    {
        $payment = $event->payment;

    $payload = [
        'payment_id' => $payment->id,
        'status' => $event instanceof PaymentSucceeded ? 'success' : 'failed',
        'amount' => $payment->amount,
        'currency' => $payment->currency,
    ];

    $signature = hash_hmac(
        'sha256',
        json_encode($payload),
        config('services.webhook.secret')
    );

    Http::withHeaders([
        'X-Signature' => $signature,
    ])->post(config('services.webhook.url'), $payload);
    }
}
