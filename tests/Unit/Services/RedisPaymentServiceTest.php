<?php

namespace Tests\Unit\Services;

use App\Services\RedisPaymentService;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class RedisPaymentServiceTest extends TestCase
{
    public function test_payment_state_is_cached_and_retrieved(): void
    {
        $redis = Mockery::mock(Connection::class);

        $state = [
            'status' => 'pending',
            'failure_reason' => null,
        ];

        $redis->shouldReceive('setex')
            ->once()
            ->with(
                'payment:state:payment-1',
                Mockery::any(),
                json_encode($state)
            );

        $redis->shouldReceive('get')
            ->once()
            ->with('payment:state:payment-1')
            ->andReturn(json_encode($state));

        $service = new RedisPaymentService($redis);

        $service->setPaymentState('payment-1', $state);
        $result = $service->getPaymentState('payment-1');

        $this->assertEquals($state, $result);
    }

    public function test_redis_failure_is_gracefully_handled(): void
    {
        Log::spy();

        $redis = Mockery::mock(Connection::class);

        $redis->shouldReceive('get')
            ->once()
            ->andThrow(new \Exception('Redis down'));

        $service = new RedisPaymentService($redis);

        $this->assertNull(
            $service->getPaymentState('payment-1')
        );
    }

    public function test_with_payment_lock_does_not_execute_when_lock_not_acquired(): void
    {
        $redis = Mockery::mock(Connection::class);

        $redis->shouldReceive('set')->once()->andReturn(false);
        $redis->shouldReceive('eval')->never();

        $service = new RedisPaymentService($redis);

        $called = false;

        $service->withPaymentLock('payment-1', fn () => $called = true);

        $this->assertFalse($called);
    }
}
