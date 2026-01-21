<?php

namespace App\Repositories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentRepository
{
    /**
     * Lock payment row only if still pending, otherwise return null
     */
    private function lockPending(Payment $payment): ?Payment
    {
        return Payment::where('id', $payment->id)
            ->where('status', PaymentStatus::Pending->value)
            ->lockForUpdate()
            ->first();
    }

    public function markSuccess(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $locked = $this->lockPending($payment);

            if (! $locked) {
                return; // already finalized
            }

            $locked->status = PaymentStatus::Success->value;
            $locked->save();
        });
    }

    public function markFailed(Payment $payment, string $reason): void
    {
        DB::transaction(function () use ($payment, $reason) {
            $locked = $this->lockPending($payment);

            if (! $locked) {
                return; // already finalized
            }

            $locked->status = PaymentStatus::Failed->value;
            $locked->failure_reason = $reason;
            $locked->save();
        });
    }
}
