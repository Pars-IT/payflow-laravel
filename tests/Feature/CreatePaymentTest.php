<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Jobs\ProcessPaymentJob;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreatePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_gateway_payment_is_created_and_job_dispatched(): void
    {
        Queue::fake();

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

        $this->assertNotNull($payment);
        $this->assertEquals(PaymentStatus::Pending->value, $payment->status);

        Queue::assertPushed(ProcessPaymentJob::class, function ($job) use ($payment) {
            return $job->paymentId === $payment->id;
        });
    }

    public function test_payment_is_created_even_if_gateway_will_fail(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Wallet::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->postJson('/api/payments', [
            'user_id' => $user->id,
            'gateway' => 'abn-amro',
            'amount' => 1501, // odd → will fail in job
            'idempotency_key' => 'test-2',
        ]);

        $response->assertStatus(201);

        $payment = Payment::first();

        $this->assertEquals(PaymentStatus::Pending->value, $payment->status);

        Queue::assertPushed(ProcessPaymentJob::class);
    }

    public function test_idempotency_prevents_duplicate_payment(): void
    {
        Queue::fake();

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
