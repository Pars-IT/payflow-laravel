<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ReconcileWalletsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct()
    {
        $this->onQueue('reconciliation');
    }

    public function handle(): void
    {
        Wallet::chunk(100, function ($wallets) {
            foreach ($wallets as $wallet) {
                $calculated = (int) Transaction::where('wallet_id', $wallet->id)
                    ->sum('amount');

                if ($wallet->balance !== $calculated) {
                    Log::warning('Wallet mismatch (cron)', [
                        'wallet_id' => $wallet->id,
                        'stored' => $wallet->balance,
                        'calculated' => $calculated,
                    ]);

                    // auto-fix (optional)
                    $wallet->balance = $calculated;
                    $wallet->save();
                }
            }
        });

        Log::info('Wallet reconciliation job completed');
    }
}
