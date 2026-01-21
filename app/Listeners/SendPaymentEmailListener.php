<?php

namespace App\Listeners;

use App\Enums\PaymentStatus;
use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Mail\PaymentStatusMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendPaymentEmailListener implements ShouldQueue
{
    public int $tries = 3;

    public int $backoff = 10;

    /**
     * Handle the event.
     */
    public function handle(PaymentSucceeded|PaymentFailed $event): void
    {
        $payment = $event->payment;
        $status = $event instanceof PaymentSucceeded ? PaymentStatus::Success->value : PaymentStatus::Failed->value;
        $reason = $event instanceof PaymentFailed ? $event->reason : null;
        Mail::to($payment->user->email)
            ->send(new PaymentStatusMail($payment, $status, $reason));
    }
}
