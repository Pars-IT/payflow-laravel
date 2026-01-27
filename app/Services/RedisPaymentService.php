<?php

namespace App\Services;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class RedisPaymentService
{
    /* ---------------- Idempotency ---------------- */

    public function getPaymentByIdempotency(string $key): ?string
    {
        return $this->safe(
            fn () => Cache::get($this->idempotencyKey($key))
        );
    }

    public function storeIdempotency(
        string $key,
        string $paymentId,
        int $ttlSeconds = 600
    ): void {
        $this->safe(
            fn () => Cache::put(
                $this->idempotencyKey($key),
                $paymentId,
                $ttlSeconds
            )
        );
    }

    protected function idempotencyKey(string $key): string
    {
        return "idempotency:{$key}";
    }

    /* ---------------- Payment state (UI polling) ---------------- */

    public function setPaymentState(
        string $paymentId,
        array $state,
        int $ttlSeconds = 300
    ): void {
        $this->safe(
            fn () => Cache::put(
                $this->stateKey($paymentId),
                $state,
                $ttlSeconds
            )
        );
    }

    public function getPaymentState(string $paymentId): ?array
    {
        return $this->safe(
            fn () => Cache::get($this->stateKey($paymentId)),
        );
    }

    protected function stateKey(string $paymentId): string
    {
        return "payment:state:{$paymentId}";
    }

    /* ---------------- Distributed lock ---------------- */

    public function acquirePaymentLock(
        string $paymentId,
        int $seconds = 30
    ): ?Lock {
        return $this->safe(
            function () use ($paymentId, $seconds) {
                $lock = Cache::lock($this->lockKey($paymentId), $seconds);

                return $lock->get() ? $lock : null;
            }
        );
    }

    public function withPaymentLock(
        string $paymentId,
        callable $callback,
        int $seconds = 30
    ): void {
        $this->safe(
            function () use ($paymentId, $seconds, $callback) {
                $lock = Cache::lock($this->lockKey($paymentId), $seconds);

                if (! $lock->get()) {
                    return;
                }

                try {
                    $callback();
                } finally {
                    $lock->release();
                }
            }
        ) ?? $callback(); // Redis down → run anyway
    }

    protected function lockKey(string $paymentId): string
    {
        return "payment:lock:{$paymentId}";
    }

    /* ---------------- Safety wrapper ---------------- */

    private function safe(
        callable $callback,
        mixed $default = null
    ): mixed {
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
