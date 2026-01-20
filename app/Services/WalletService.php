<?php

namespace App\Services;

use App\Exceptions\Payments\WalletNotFoundException;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Process money (ledger)
     */
    public function creditFromPayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $wallet = Wallet::where('user_id', $payment->user_id)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                throw new WalletNotFoundException;
            }

            $wallet->balance += $payment->amount;
            $wallet->save();

            Transaction::create([
                'wallet_id' => $wallet->id,
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'type' => 'credit',
            ]);
        });
    }
}
