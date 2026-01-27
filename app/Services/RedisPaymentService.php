<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Redis;
use Throwable;

class RedisPaymentService
{
    public function __construct(
        private readonly Redis $redis
    ) {}

    /* ---------------- Payment state (UI polling) ---------------- */

    public function setPaymentState(
        string $paymentId,
        array $state,
        int $ttlSeconds = 300
    ): void {
        $this->safe(function () use ($paymentId, $state, $ttlSeconds) {
            $this->redis->setex(
                $this->stateKey($paymentId),
                $ttlSeconds,
                json_encode($state, JSON_THROW_ON_ERROR)
            );
        });
    }

    public function getPaymentState(string $paymentId): ?array
    {
        return $this->safe(function () use ($paymentId) {
            $value = $this->redis->get($this->stateKey($paymentId));

            return $value ? json_decode($value, true, 512, JSON_THROW_ON_ERROR) : null;
        });
    }

    private function stateKey(string $paymentId): string
    {
        return "payment:state:{$paymentId}";
    }

    /* ---------------- Distributed lock (SAFE) ---------------- */

    public function withPaymentLock(
        string $paymentId,
        callable $callback,
        int $ttlSeconds = 30
    ): void {
        $lockKey = $this->lockKey($paymentId);
        $token = bin2hex(random_bytes(16));

        $acquired = $this->safe(fn () => $this->redis->set($lockKey, $token, ['nx', 'ex' => $ttlSeconds]),
            false
        );

        if (! $acquired) {
            return;
        }

        try {
            $callback();
        } finally {
            $this->releaseLock($lockKey, $token);
        }
    }

    private function releaseLock(string $key, string $token): void
    {
        // Lua → atomic check & delete
        $lua = <<<'LUA'
if redis.call("GET", KEYS[1]) == ARGV[1] then
    return redis.call("DEL", KEYS[1])
end
return 0
LUA;

        $this->safe(fn () => $this->redis->eval($lua, [$key, $token], 1)
        );
    }

    private function lockKey(string $paymentId): string
    {
        return "payment:lock:{$paymentId}";
    }

    /* ---------------- Safety wrapper ---------------- */

    private function safe(callable $callback, mixed $default = null): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            Log::warning('Redis operation failed', [
                'context' => __METHOD__,
                'exception' => $e,
            ]);

            return $default;
        }
    }
}
