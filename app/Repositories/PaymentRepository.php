<?php

namespace App\Repositories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function findById(string $id): Payment
    {
        return Payment::findOrFail($id);
    }

    public function findByIdempotencyKey(string $key): ?Payment
    {
        return Payment::where('idempotency_key', $key)->first();
    }

    public function findByProviderPaymentId(
        string $provider,
        string $providerPaymentId
    ): Payment {
        return Payment::where('provider', $provider)
            ->where('provider_payment_id', $providerPaymentId)
            ->firstOrFail();
    }

    public function markTimedOut(
        Payment $payment,
    ): bool {
        return Payment::where('id', $payment->id)
            ->where('status', PaymentStatus::Pending->value)
            ->update([
                'status' => PaymentStatus::Failed->value,
                'failure_reason' => 'processing_timeout',
            ]) === 1;
    }

    public function createPending(array $data): Payment
    {
        return Payment::create([
            'id' => (string) Str::uuid(),
            'user_id' => $data['user_id'],
            'gateway' => $data['gateway'],
            'amount' => $data['amount'],
            'currency' => 'EUR',
            'status' => PaymentStatus::Pending->value,
            'idempotency_key' => $data['idempotency_key'],
        ]);
    }

    public function attachProviderData(
        string $paymentId,
        string $provider,
        string $providerPaymentId,
        string $checkoutUrl
    ): void {
        Payment::where('id', $paymentId)->update([
            'provider' => $provider,
            'provider_payment_id' => $providerPaymentId,
            'provider_checkout_url' => $checkoutUrl,
        ]);
    }

    /**
     * Try to finalize a pending payment.
     * Returns true if state changed, false if already finalized.
     */
    public function markSuccess(string $paymentId): bool
    {
        return DB::transaction(function () use ($paymentId) {
            $payment = Payment::where('id', $paymentId)
                ->where('status', PaymentStatus::Pending->value)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return false;
            }

            $payment->update([
                'status' => PaymentStatus::Success->value,
            ]);

            return true;
        });
    }

    public function markFailed(string $paymentId, string $reason): bool
    {
        return DB::transaction(function () use ($paymentId, $reason) {
            $payment = Payment::where('id', $paymentId)
                ->where('status', PaymentStatus::Pending->value)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return false;
            }

            $payment->update([
                'status' => PaymentStatus::Failed->value,
                'failure_reason' => $reason,
            ]);

            return true;
        });
    }
}
