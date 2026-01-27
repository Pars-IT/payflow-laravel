<?php

namespace App\Repositories\Contracts;

use App\Models\Payment;

interface PaymentRepositoryInterface
{
    public function findById(string $id): Payment;

    public function findByIdempotencyKey(string $key): ?Payment;

    public function findByProviderPaymentId(
        string $provider,
        string $providerPaymentId
    ): Payment;

    public function markTimedOut(Payment $payment): bool;

    public function createPending(array $data): Payment;

    public function attachProviderData(
        string $paymentId,
        string $provider,
        string $providerPaymentId,
        string $checkoutUrl
    ): void;

    /**
     * Returns true if state changed, false if already finalized.
     */
    public function markSuccess(string $paymentId): bool;

    public function markFailed(string $paymentId, string $reason): bool;
}
