<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class RedisPaymentService
{
    /* ---------------- Payment state (UI polling) ---------------- */

    public function setPaymentState(
        string $paymentId,
        array $state,
        int $ttlSeconds = 300
    ): void {
        try {
            Cache::put($this->stateKey($paymentId), $state, $ttlSeconds);
        } catch (Throwable $e) {
            Log::warning('Cache setPaymentState failed', [
                'payment_id' => $paymentId,
                'exception' => $e,
            ]);
        }
    }

    public function getPaymentState(string $paymentId): ?array
    {
        try {
            return Cache::get($this->stateKey($paymentId));
        } catch (Throwable $e) {
            Log::warning('Cache getPaymentState failed', [
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
        try {
            Cache::lock($this->lockKey($paymentId), $ttlSeconds)
                ->block(0, function () use ($callback) {
                    $callback();
                });
        } catch (Throwable $e) {
            Log::warning('Cache lock failed, running without lock', [
                'payment_id' => $paymentId,
                'exception' => $e,
            ]);

            $callback();
        }
    }

    private function lockKey(string $paymentId): string
    {
        return "payment:lock:{$paymentId}";
    }
}
