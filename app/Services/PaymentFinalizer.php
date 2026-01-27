<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;

class PaymentFinalizer
{
    public function __construct(
        private PaymentRepositoryInterface $payments,
        private RedisPaymentService $redisPaymentService
    ) {}

    public function succeed(Payment $payment): void
    {
        if (! $this->payments->markSuccess($payment->id)) {
            return; // already finalized
        }

        // Redis = best-effort
        $this->redisPaymentService->setPaymentState($payment->id, [
            'status' => PaymentStatus::Success->value,
        ]);

        PaymentSucceeded::dispatch($payment);
    }

    public function fail(Payment $payment, string $reason): void
    {
        if (! $this->payments->markFailed($payment->id, $reason)) {
            return; // already finalized
        }

        // Redis = best-effort
        $this->redisPaymentService->setPaymentState($payment->id, [
            'status' => PaymentStatus::Failed->value,
            'failure_reason' => $reason,
        ]);

        PaymentFailed::dispatch($payment, $reason);
    }
}
