<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_gateway_payment_succeeds(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        $response = $this->postJson('/api/payments', [
            'user_id' => $user->id,
            'gateway' => 'ing',
            'amount' => 1500,
            'idempotency_key' => 'test-1',
        ]);

        $response->assertStatus(201);

        $payment = Payment::first();

        $this->assertEquals('success', $payment->status);
    }

    public function test_payment_fails_with_invalid_amount(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->postJson('/api/payments', [
            'user_id' => $user->id,
            'gateway' => 'abn-amro',
            'amount' => 1501,
            'idempotency_key' => 'test-2',
        ]);

        $payment = Payment::first();

        $this->assertEquals('failed', $payment->status);
        $this->assertEquals('abn_amro_rejected', $payment->failure_reason);
    }

    public function test_idempotency_prevents_duplicate_payment(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->create(['user_id' => $user->id]);

        $payload = [
            'user_id' => $user->id,
            'gateway' => 'ing',
            'amount' => 1500,
            'idempotency_key' => 'same-key',
        ];

        $this->postJson('/api/payments', $payload);
        $this->postJson('/api/payments', $payload);

        $this->assertEquals(1, Payment::count());
    }
}
