<?php

namespace Tests\Unit\Services;

use App\Enums\PaymentStatus;
use App\Jobs\ProcessPaymentJob;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Payments\GatewayResolver;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Services\PaymentFinalizer;
use App\Services\RedisPaymentService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Tests\TestCase;

class ProcessPaymentJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_payment_succeeds(): void
    {
        Event::fake();

        $user = User::factory()->create();
        Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 2000,
            'gateway' => 'abn-amro',
            'status' => PaymentStatus::Pending->value,
        ]);

        // fake redis (lock always acquired)
        /** @var RedisPaymentService&MockInterface $redis */
        $redis = $this->mock(RedisPaymentService::class, function ($mock) {
            $mock->shouldReceive('withPaymentLock')
                ->once()
                ->andReturnUsing(fn ($id, $cb) => $cb());

            $mock->shouldIgnoreMissing();
        });

        $job = new ProcessPaymentJob($payment->id);
        $job->handle(
            $redis,
            app()->make(GatewayResolver::class),
            app()->make(WalletService::class),
            app()->make(PaymentFinalizer::class),
            app()->make(PaymentRepositoryInterface::class)
        );

        $payment->refresh();

        $this->assertEquals(PaymentStatus::Success->value, $payment->status);
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 2000,
        ]);
    }

    public function test_payment_fails_when_gateway_rejects(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->create(['user_id' => $user->id]);

        // odd amount => ABN AMRO rejects
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 1501,
            'gateway' => 'abn-amro',
            'status' => PaymentStatus::Pending->value,
        ]);

        /** @var RedisPaymentService&MockInterface $redis */
        $redis = $this->mock(RedisPaymentService::class, function ($mock) {
            $mock->shouldReceive('withPaymentLock')
                ->once()
                ->andReturnUsing(fn ($id, $cb) => $cb());

            $mock->shouldIgnoreMissing();
        });

        $job = new ProcessPaymentJob($payment->id);
        $job->handle(
            $redis,
            app()->make(GatewayResolver::class),
            app()->make(WalletService::class),
            app()->make(PaymentFinalizer::class),
            app()->make(PaymentRepositoryInterface::class)
        );

        $payment->refresh();

        $this->assertEquals(PaymentStatus::Failed->value, $payment->status);
        $this->assertEquals('abn_amro_rejected', $payment->failure_reason);
    }

    public function test_job_does_nothing_if_payment_already_processed(): void
    {
        $user = User::factory()->create();

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::Success->value,
        ]);

        /** @var RedisPaymentService&MockInterface $redis */
        $redis = $this->mock(RedisPaymentService::class);
        $redis->shouldReceive('withPaymentLock')
            ->once()
            ->andReturnUsing(fn ($id, $cb) => $cb());

        $job = new ProcessPaymentJob($payment->id);
        $job->handle(
            $redis,
            app()->make(GatewayResolver::class),
            app()->make(WalletService::class),
            app()->make(PaymentFinalizer::class),
            app()->make(PaymentRepositoryInterface::class)
        );

        // nothing changed
        $this->assertEquals(PaymentStatus::Success->value, $payment->fresh()->status);
    }
}
