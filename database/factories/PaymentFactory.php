<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'gateway' => 'ideal',
            'provider' => null,
            'provider_payment_id' => null,
            'provider_checkout_url' => null,
            'amount' => 1500,
            'currency' => 'EUR',
            'status' => PaymentStatus::Pending->value,
            'idempotency_key' => (string) Str::uuid(),
            'failure_reason' => null,
        ];
    }

    public function success(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Success->value,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Failed->value,
            'failure_reason' => 'PSP_ERROR',
        ]);
    }
}
