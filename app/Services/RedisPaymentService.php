<?php

namespace App\Services;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

class RedisPaymentService
{
    public function getPaymentByIdempotency(string $key): ?string
    {
        return Cache::get($this->idempotencyKey($key));
    }

    public function storeIdempotency(string $key, string $paymentId, int $ttlSeconds = 600): void
    {
        Cache::put(
            $this->idempotencyKey($key),
            $paymentId,
            $ttlSeconds
        );
    }

    protected function idempotencyKey(string $key): string
    {
        return "idempotency:{$key}";
    }

    public function setPaymentStatus(string $paymentId, string $status, int $ttlSeconds = 300): void
    {
        Cache::put(
            $this->statusKey($paymentId),
            $status,
            $ttlSeconds
        );
    }

    public function getPaymentStatus(string $paymentId): ?string
    {
        return Cache::get($this->statusKey($paymentId));
    }

    protected function statusKey(string $paymentId): string
    {
        return "payment:status:{$paymentId}";
    }

    /**
     * Use Redis-based distributed locks with TTL and safe release semantics to prevent concurrent payment processing.
     */
    public function acquirePaymentLock(string $paymentId, int $seconds = 30): ?Lock
    {
        $lock = Cache::lock(
            $this->lockKey($paymentId),
            $seconds
        );

        return $lock->get() ? $lock : null;
    }

    protected function lockKey(string $paymentId): string
    {
        return "payment:lock:{$paymentId}";
    }
}
