<?php

namespace App\Repositories;

use App\Models\Payment;

class PaymentRepository
{
    public function markSuccess(Payment $payment): void
    {
        $payment->status = 'success';
        $payment->failure_reason = null;
        $payment->save();
    }

    public function markFailed(Payment $payment, string $reason): void
    {
        $payment->status = 'failed';
        $payment->failure_reason = $reason;
        $payment->save();
    }
}
