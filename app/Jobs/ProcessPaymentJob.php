<?php

namespace App\Jobs;

use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Payments\GatewayResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class ProcessPaymentJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public string $paymentId
    ) {}

    public function handle(): void
    {
        $payment = Payment::find($this->paymentId);

        // Payment already processed or missing
        if (! $payment || $payment->status !== 'pending') {
            return;
        }

        $resolver = new GatewayResolver();
        $gateway = $resolver->resolve($payment);

        $result = $gateway->charge($payment);

        if (! $result->success) {
            event(new PaymentFailed($payment, $result->failureReason ?? 'unknown'));
            return;
        }


        /**
         * Process money (ledger)
         */
        DB::transaction(function () use ($payment) {

            $wallet = Wallet::where('user_id', $payment->user_id)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                event(new PaymentFailed($payment, 'wallet_not_found'));
                return;
            }

            // update balance
            $wallet->balance += $payment->amount;
            $wallet->save();

            // ledger entry
            Transaction::create([
                'wallet_id'  => $wallet->id,
                'payment_id' => $payment->id,
                'amount'     => $payment->amount,
                'type'       => 'credit',
            ]);
        });

        event(new PaymentSucceeded($payment));
    }
}
