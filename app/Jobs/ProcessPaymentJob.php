<?php

namespace App\Jobs;

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
        RedisPaymentService $redis
    ): void {
        /**
         * Acquire Redis lock to prevent double processing
         */
        $lock = $redis->acquirePaymentLock($this->paymentId);

        if (! $lock) {
            // another worker is already processing this payment
            return;
        }

        try {
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

                /**
                 * Business-level failure
                 */
                if (! $result->success) {
                    $finalizer->fail(
                        $payment,
                        $result->failureReason
                    );

                    return;
                }

                /**
                 * Async gateways (e.g. Mollie)
                 * Finalized later via webhook
                 */
                if ($result->async === true) {
                    return;
                }

                /**
                 * Sync gateways
                 */
                app(WalletService::class)
                    ->creditFromPayment($payment);

                $finalizer->succeed($payment);

            } catch (PspException $e) {
                $finalizer->fail(
                    $payment,
                    $e->reason()
                );
            } catch (WalletNotFoundException $e) {
                $finalizer->fail(
                    $payment,
                    'wallet_not_found'
                );
            }

        } finally {
            /**
             * Always release Redis lock
             */
            $lock->release();
        }
    }
}
