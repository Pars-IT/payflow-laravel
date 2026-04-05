<?php

namespace App\Http\Controllers;

use App\Models\Wallet;

class WalletController extends Controller
{
    public function show($userId)
    {
        $wallet = Wallet::where('user_id', $userId)->first();

        if (! $wallet) {
            return response()->json([
                'user_id' => $userId,
                'balance' => 0,
            ]);
        }

        return response()->json([
            'user_id' => $userId,
            'balance' => $wallet->balance,
        ]);
    }
}
