<?php

namespace Tests\Unit;

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

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 1500,
        ]);
    }
}
