<?php

namespace App\Services;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RedisPaymentService
{
    /* ---------------- Idempotency ---------------- */

    public function getPaymentByIdempotency(string $key): ?string
    {
        try {
            return Cache::get($this->idempotencyKey($key));
        } catch (Throwable) {
            return null;
        }
    }

    public function storeIdempotency(
        string $key,
        string $paymentId,
        int $ttlSeconds = 600
    ): void {
        try {
            Cache::put(
                $this->idempotencyKey($key),
                $paymentId,
                $ttlSeconds
            );
        } catch (Throwable) {
            // Skip on Redis failure
        }
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
        try {
            Cache::put(
                $this->stateKey($paymentId),
                $state,
                $ttlSeconds
            );
        } catch (Throwable) {
            // Skip on Redis failure
        }
    }

    public function getPaymentState(string $paymentId): ?array
    {
        try {
            return Cache::get($this->stateKey($paymentId));
        } catch (Throwable) {
            return null;
        }
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
        try {
            $lock = Cache::lock(
                $this->lockKey($paymentId),
                $seconds
            );

            return $lock->get() ? $lock : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function withPaymentLock(
        string $paymentId,
        callable $callback,
        int $seconds = 30
    ): void {
        try {
            $lock = Cache::lock($this->lockKey($paymentId), $seconds);

            if (! $lock->get()) {
                return;
            }

            try {
                $callback();
            } finally {
                $lock->release();
            }
        } catch (Throwable) {
            // Redis down → execute anyway
            $callback();
        }
    }

    protected function lockKey(string $paymentId): string
    {
        return "payment:lock:{$paymentId}";
    }
}
