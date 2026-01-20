<?php

namespace App\Services;

use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Models\Payment;
use App\Repositories\PaymentRepository;

class PaymentFinalizer
{
    public function __construct(
        private PaymentRepository $payments,
        private RedisPaymentService $redis
    ) {}

    public function succeed(Payment $payment): void
    {
        /**
         * Idempotency guard
         */
        if ($payment->status === 'success') {
            return;
        }

        /**
         * Persist state (source of truth)
         */
        $this->payments->markSuccess($payment);

        /**
         * Cache hot state (polling / UI)
         */
        $this->redis->setPaymentStatus(
            $payment->id,
            'success'
        );

        /**
         * Emit domain event
         */
        event(new PaymentSucceeded($payment));
    }

    public function fail(Payment $payment, string $reason): void
    {
        /**
         * Idempotency guard
         */
        if ($payment->status === 'failed') {
            return;
        }

        /**
         * Persist state (source of truth)
         */
        $this->payments->markFailed(
            $payment,
            $reason
        );

        /**
         * Cache hot state (polling / UI)
         */
        $this->redis->setPaymentStatus(
            $payment->id,
            'failed'
        );

        /**
         * Emit domain event
         */
        event(new PaymentFailed(
            $payment,
            $reason
        ));
    }
}
