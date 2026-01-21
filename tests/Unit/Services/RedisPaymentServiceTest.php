<?php

namespace Tests\Unit\Services;

use App\Enums\PaymentStatus;
use App\Services\RedisPaymentService;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class RedisPaymentServiceTest extends TestCase
{
    public function test_idempotency_is_stored_and_retrieved(): void
    {
        Cache::shouldReceive('put')
            ->once()
            ->with('idempotency:key-1', 'payment-123', Mockery::any());

        Cache::shouldReceive('get')
            ->once()
            ->with('idempotency:key-1')
            ->andReturn('payment-123');

        $redis = new RedisPaymentService;

        $redis->storeIdempotency('key-1', 'payment-123');
        $result = $redis->getPaymentByIdempotency('key-1');

        $this->assertEquals('payment-123', $result);
    }

    public function test_payment_state_is_cached_and_retrieved(): void
    {
        $state = [
            'status' => PaymentStatus::Pending->value,
            'failure_reason' => null,
            'checkout_url' => 'https://checkout.test',
        ];

        Cache::shouldReceive('put')
            ->once()
            ->with('payment:state:payment-1', $state, Mockery::any());

        Cache::shouldReceive('get')
            ->once()
            ->with('payment:state:payment-1')
            ->andReturn($state);

        $redis = new RedisPaymentService;

        $redis->setPaymentState('payment-1', $state);
        $result = $redis->getPaymentState('payment-1');

        $this->assertEquals($state, $result);
    }

    public function test_redis_failure_is_gracefully_handled(): void
    {
        Cache::shouldReceive('get')
            ->once()
            ->andThrow(new \Exception('Redis down'));

        $redis = new RedisPaymentService;

        $result = $redis->getPaymentState('payment-1');

        $this->assertNull($result);
    }

    public function test_with_payment_lock_executes_callback(): void
    {
        $lock = Mockery::mock(Lock::class);
        $lock->shouldReceive('get')->once()->andReturn(true);
        $lock->shouldReceive('release')->once();

        Cache::shouldReceive('lock')
            ->once()
            ->with('payment:lock:payment-1', Mockery::any())
            ->andReturn($lock);

        $redis = new RedisPaymentService;

        $called = false;

        $redis->withPaymentLock('payment-1', function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
    }
}
