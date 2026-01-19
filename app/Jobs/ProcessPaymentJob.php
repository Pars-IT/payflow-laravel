<?php

namespace App\Jobs;

use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Models\Payment;
use App\Payments\GatewayResolver;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

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

        $result = $gateway->charge($payment);

        if (! $result->success) {
            $payment->status = 'failed';
            $payment->save();

            event(new PaymentFailed(
                $payment,
                $result->failureReason ?? 'unknown'
            ));

            return;
        }

        // ASYNC gateways (e.g. Mollie)
        if ($result->async) {
            return;
        }

        // SYNC SUCCESS
        try {
            DB::transaction(function () use ($payment) {
                app(WalletService::class)->creditFromPayment($payment);

                $payment->status = 'success';
                $payment->save();
            });

            event(new PaymentSucceeded($payment));
        } catch (\RuntimeException $e) {
            $payment->status = 'failed';
            $payment->save();

            event(new PaymentFailed($payment, $e->getMessage()));
        }
    }
}
