<?php

namespace App\Services;

use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Models\Payment;
use App\Repositories\PaymentRepository;

class PaymentFinalizer
{
    public function __construct(
        private PaymentRepository $payments
    ) {}

    public function succeed(Payment $payment): void
    {
        $this->payments->markSuccess($payment);
        event(new PaymentSucceeded($payment));
    }

    public function fail(Payment $payment, string $reason): void
    {
        $this->payments->markFailed($payment, $reason);
        event(new PaymentFailed($payment, $reason));
    }
}
