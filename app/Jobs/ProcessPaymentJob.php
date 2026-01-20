<?php

namespace App\Jobs;

use App\Exceptions\Payments\PspException;
use App\Exceptions\WalletNotFoundException;
use App\Models\Payment;
use App\Payments\GatewayResolver;
use App\Services\PaymentFinalizer;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessPaymentJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public string $paymentId
    ) {}

    public function handle(): void
    {
        $payment = Payment::find($this->paymentId);

        // Payment already processed or missing
        if (! $payment || $payment->status !== 'pending') {
            return;
        }

        $resolver = new GatewayResolver;
        $gateway = $resolver->resolve($payment);
        $finalizer = app(PaymentFinalizer::class);
        try {
            $result = $gateway->charge($payment);

            if (! $result->success) {
                $finalizer->fail($payment, $result->failureReason);

                return;
            }
            // ASYNC gateways (e.g. Mollie)
            if ($result->async === true) {
                // async PSPs are finalized via webhook
                return;
            }

            // SYNC gateways
            app(WalletService::class)->creditFromPayment($payment);

            $finalizer->succeed($payment);

        } catch (PspException $e) {
            $finalizer->fail($payment, $e->reason());
        } catch (WalletNotFoundException $e) {
            $finalizer->fail($payment, 'wallet_not_found');
        }
    }
}
