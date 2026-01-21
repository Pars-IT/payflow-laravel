<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use Throwable;

class PaymentFinalizer
{
    public function __construct(
        private PaymentRepository $payments,
        private RedisPaymentService $redis
    ) {}

    public function succeed(Payment $payment): void
    {
        // 1. DB = source of truth
        $this->payments->markSuccess($payment);

        // 2. Redis = best-effort cache for UI
        $this->safeRedis(function () use ($payment) {
            $this->redis->setPaymentState($payment->id, [
                'status' => PaymentStatus::Success->value,
            ]);
        });

        event(new PaymentSucceeded($payment));
    }

    public function fail(Payment $payment, string $reason): void
    {
        // 1. DB
        $this->payments->markFailed($payment, $reason);

        // 2. Redis
        $this->safeRedis(function () use ($payment, $reason) {
            $this->redis->setPaymentState($payment->id, [
                'status' => PaymentStatus::Failed->value,
                'failure_reason' => $reason,
            ]);
        });

        event(new PaymentFailed($payment, $reason));
    }

    private function safeRedis(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable) {
            // Redis is optional, never block payment flow
        }
    }
}
