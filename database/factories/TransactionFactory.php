<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'wallet_id' => 1,
            'payment_id' => null,
            'amount' => 1000,
            'type' => 'credit',
        ];
    }
}
