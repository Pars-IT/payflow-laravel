<?php

namespace Tests\Unit\Services;

use App\Services\RedisPaymentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RedisPaymentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_payment_state_is_cached_and_retrieved(): void
    {
        $service = new RedisPaymentService;

        $state = [
            'status' => 'pending',
            'failure_reason' => null,
        ];

        $service->setPaymentState('payment-1', $state);

        $result = $service->getPaymentState('payment-1');

        $this->assertEquals($state, $result);
    }

    public function test_cache_failure_is_gracefully_handled(): void
    {
        Log::spy();

        Cache::shouldReceive('get')
            ->once()
            ->andThrow(new \Exception('Cache down'));

        $service = new RedisPaymentService;

        $this->assertNull(
            $service->getPaymentState('payment-1')
        );

        Log::shouldHaveReceived('warning')->once();
    }

    public function test_with_payment_lock_executes_callback(): void
    {
        $service = new RedisPaymentService;

        $called = false;

        $service->withPaymentLock('payment-1', function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
    }

    public function test_with_payment_lock_executes_callback_once(): void
    {
        $service = new RedisPaymentService;

        $count = 0;

        $service->withPaymentLock('payment-1', function () use (&$count) {
            $count++;
        });

        $this->assertEquals(1, $count);
    }
}
