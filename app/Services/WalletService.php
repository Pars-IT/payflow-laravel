<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Wallet;
use RuntimeException;

class WalletService
{
    /**
     * Process money (ledger)
     */
    public function creditFromPayment(Payment $payment): void
    {
        $wallet = Wallet::where('user_id', $payment->user_id)
            ->lockForUpdate()
            ->first();

        if (! $wallet) {
            throw new RuntimeException('wallet_not_found');
        }

        $wallet->balance += $payment->amount;
        $wallet->save();

        Transaction::create([
            'wallet_id' => $wallet->id,
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'type' => 'credit',
        ]);
    }
}
