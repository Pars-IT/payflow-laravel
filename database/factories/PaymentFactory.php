<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
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
            'user_id' => 1,
            'gateway' => 'ideal',
            'amount' => 1500,
            'currency' => 'EUR',
            'status' => PaymentStatus::Pending->value,
            'idempotency_key' => $this->faker->uuid(),
            'failure_reason' => null,
        ];
    }
}
