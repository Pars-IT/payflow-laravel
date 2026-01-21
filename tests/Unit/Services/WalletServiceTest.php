<?php

namespace Tests\Unit\Services;

use App\Exceptions\Payments\WalletNotFoundException;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_is_credited_from_payment(): void
    {
        $user = User::factory()->create();

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 1500,
        ]);

        app(WalletService::class)->creditFromPayment($payment);

        $wallet->refresh();

        $this->assertEquals(1500, $wallet->balance);
    }

    public function test_wallet_is_credited_and_transaction_created(): void
    {
        $user = User::factory()->create();

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 1000,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 500,
        ]);

        app(WalletService::class)->creditFromPayment($payment);

        $wallet->refresh();

        $this->assertEquals(1500, $wallet->balance);

        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $wallet->id,
            'payment_id' => $payment->id,
            'amount' => 500,
            'type' => 'credit',
        ]);
    }

    public function test_credit_fails_when_wallet_not_found(): void
    {
        $user = User::factory()->create();

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 500,
        ]);

        $this->expectException(WalletNotFoundException::class);

        app(WalletService::class)->creditFromPayment($payment);
    }
}
