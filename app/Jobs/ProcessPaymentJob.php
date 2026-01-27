<?php

namespace App\Jobs;

use App\Enums\PaymentStatus;
use App\Exceptions\Payments\PspException;
use App\Exceptions\Payments\WalletNotFoundException;
use App\Models\Payment;
use App\Payments\GatewayResolver;
use App\Services\PaymentFinalizer;
use App\Services\RedisPaymentService;
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

    public function handle(
        RedisPaymentService $redis,
        GatewayResolver $resolver,
        WalletService $wallets,
        PaymentFinalizer $finalizer
    ): void {
        $redis->withPaymentLock($this->paymentId, function () use (
            $resolver,
            $wallets,
            $finalizer
        ) {
            $payment = Payment::find($this->paymentId);

            // already handled or missing
            if (! $payment || PaymentStatus::from($payment->status)->isFinal()) {
                return;
            }

            try {
                $gateway = $resolver->resolve($payment);
                $result = $gateway->charge($payment);

                // business failure
                if (! $result->success) {
                    $finalizer->fail($payment, $result->failureReason);

                    return;
                }

                // async PSP (e.g. Mollie)
                if ($result->async === true) {
                    return;
                }

                // sync PSP
                $wallets->creditFromPayment($payment);
                $finalizer->succeed($payment);

            } catch (PspException $e) {
                $finalizer->fail($payment, $e->reason());
            } catch (WalletNotFoundException) {
                $finalizer->fail($payment, 'wallet_not_found');
            }
        });
    }
}
