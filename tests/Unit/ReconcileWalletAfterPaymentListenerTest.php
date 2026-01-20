<?php

namespace Tests\Unit;

use App\Events\PaymentSucceeded;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconcileWalletAfterPaymentListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_is_reconciled_when_balance_mismatch(): void
    {
        $user = User::factory()->create();

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 1000,
        ]);

        Transaction::factory()->create([
            'wallet_id' => $wallet->id,
            'amount' => 1500,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
        ]);

        event(new PaymentSucceeded($payment));

        $wallet->refresh();

        $this->assertEquals(1500, $wallet->balance);
    }
}
