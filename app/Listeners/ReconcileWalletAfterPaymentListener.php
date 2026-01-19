<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * We reconcile wallets optimistically after each payment,
 * and pessimistically with a periodic reconciliation job.
 */
class ReconcileWalletAfterPaymentListener implements ShouldQueue
{
    public function handle(PaymentSucceeded $event): void
    {
        $payment = $event->payment;

        $wallet = Wallet::where('user_id', $payment->user_id)->first();

        if (! $wallet) {
            return;
        }

        $calculated = Transaction::where('wallet_id', $wallet->id)
            ->sum('amount');

        if ((int)$wallet->balance !== (int)$calculated) {
            Log::warning('Wallet mismatch after payment', [
                'wallet_id' => $wallet->id,
                'stored' => $wallet->balance,
                'calculated' => $calculated,
            ]);

            // auto correct
            $wallet->balance = $calculated;
            $wallet->save();
        }

        Log::info('Wallet reconciled after payment', [
            'wallet_id' => $wallet->id,
            'balance' => $wallet->balance,
        ]);
    }
}
