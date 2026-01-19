<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'hazrat.mohamad@gmail.com')->first();

        if (! $user) {
            return;
        }

        Wallet::updateOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
            ]
        );
    }
}
