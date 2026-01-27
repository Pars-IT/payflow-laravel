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
        try {
            $this->redis->setex(
                $this->stateKey($paymentId),
                $ttlSeconds,
                json_encode($state, JSON_THROW_ON_ERROR)
            );
        } catch (Throwable $e) {
            Log::warning('Redis setPaymentState failed', [
                'payment_id' => $paymentId,
                'exception' => $e,
            ]);
        }
    }

    public function getPaymentState(string $paymentId): ?array
    {
        try {
            $value = $this->redis->get($this->stateKey($paymentId));

            return $value
                ? json_decode($value, true, 512, JSON_THROW_ON_ERROR)
                : null;
        } catch (Throwable $e) {
            Log::warning('Redis getPaymentState failed', [
                'payment_id' => $paymentId,
                'exception' => $e,
            ]);

            return null;
        }
    }

    private function stateKey(string $paymentId): string
    {
        return "payment:state:{$paymentId}";
    }

    /* ---------------- Distributed lock (SAFE + FALLBACK) ---------------- */

    public function withPaymentLock(
        string $paymentId,
        callable $callback,
        int $ttlSeconds = 30
    ): void {
        $lockKey = $this->lockKey($paymentId);
        $token = bin2hex(random_bytes(16));

        try {
            $hasLock = $this->redis->set($lockKey, $token, ['nx', 'ex' => $ttlSeconds]);
        } catch (Throwable $e) {
            Log::warning('Redis lock failed, running without lock', [
                'payment_id' => $paymentId,
                'exception' => $e,
            ]);

            $callback();

            return;
        }

        if ($hasLock === false) {
            return; // another worker owns the lock
        }

        try {
            $callback();
        } finally {
            $this->releaseLock($lockKey, $token);
        }
    }

    private function releaseLock(string $key, string $token): void
    {
        $lua = <<<'LUA'
if redis.call("GET", KEYS[1]) == ARGV[1] then
    return redis.call("DEL", KEYS[1])
end
return 0
LUA;

        try {
            $this->redis->eval($lua, [$key, $token], 1);
        } catch (Throwable $e) {
            Log::warning('Redis releaseLock failed', [
                'lock_key' => $key,
                'token' => $token,
                'exception' => $e,
            ]);
        }
    }

    private function lockKey(string $paymentId): string
    {
        return "payment:lock:{$paymentId}";
    }
}
