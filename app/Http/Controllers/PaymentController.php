<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Jobs\ProcessPaymentJob;
use App\Models\Payment;
use App\Services\RedisPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'amount' => 'required|integer|min:1',
            'idempotency_key' => 'required|string',
        ]);

        // DB-level idempotency (safe fallback)
        $existing = Payment::where('idempotency_key', $request->idempotency_key)->first();
        if ($existing) {
            return response()->json([
                'id' => $existing->id,
                'status' => $existing->status,
            ], 200);
        }

        $payment = Payment::create([
            'id' => (string) Str::uuid(),
            'user_id' => $request->user_id,
            'gateway' => $request->gateway ?? 'ideal',
            'amount' => $request->amount,
            'currency' => 'EUR',
            'status' => PaymentStatus::Pending->value,
            'idempotency_key' => $request->idempotency_key,
        ]);

        ProcessPaymentJob::dispatch($payment->id);

        return response()->json([
            'id' => $payment->id,
            'status' => PaymentStatus::Pending->value,
        ], 201);
    }

    public function show(
        string $id,
        RedisPaymentService $redis
    ) {
        /**
         * Redis hot path
         */
        if ($state = $redis->getPaymentState($id)) {
            return response()->json(array_merge(
                ['cached' => true, 'id' => $id],
                $state
            ));
        }

        /**
         * DB source of truth
         */
        $payment = Payment::findOrFail($id);

        $response = [
            'id' => $payment->id,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'failure_reason' => $payment->failure_reason,
            'checkout_url' => $payment->provider_checkout_url,
        ];

        if (
            PaymentStatus::from($payment->status)->isPending() &&
            $payment->provider_checkout_url === null &&
            $payment->created_at->lt(now()->subMinutes(2))
        ) {
            // auto-heal
            $payment->status = PaymentStatus::Failed->value;
            $payment->failure_reason = 'processing_timeout';
            $payment->save();
        }

        /**
         * Warm Redis cache
         */
        if (
            PaymentStatus::from($payment->status)->isFinal()
            || $payment->provider_checkout_url !== null
        ) {
            $redis->setPaymentState($payment->id, [
                'status' => $payment->status,
                'failure_reason' => $payment->failure_reason,
                'checkout_url' => $payment->provider_checkout_url,
            ]);
        }

        return response()->json($response);
    }
}
