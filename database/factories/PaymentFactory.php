<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
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
            'status' => 'pending',
            'idempotency_key' => $this->faker->uuid(),
            'failure_reason' => null,
        ];
    }
}
