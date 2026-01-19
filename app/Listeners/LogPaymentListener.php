<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogPaymentListener implements ShouldQueue
{
    public int $tries = 3;

    public int $backoff = 10;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentSucceeded $event): void
    {
        Log::info('Payment succeeded', [
            'payment_id' => $event->payment->id,
            'user_id' => $event->payment->user_id,
            'amount' => $event->payment->amount,
        ]);
    }
}
