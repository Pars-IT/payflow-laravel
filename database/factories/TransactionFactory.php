<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'payment_id' => null,
            'amount' => 1000,
            'type' => 'credit',
        ];
    }

    public function debit(): static
    {
        return $this->state(fn () => [
            'type' => 'debit',
        ]);
    }
}
