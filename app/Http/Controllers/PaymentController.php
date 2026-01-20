<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPaymentJob;
use App\Models\Payment;
use App\Services\RedisPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function store(
        Request $request,
        RedisPaymentService $redis
    ) {
        $request->validate([
            'user_id' => 'required|integer',
            'amount' => 'required|integer|min:1',
            'idempotency_key' => 'required|string',
        ]);

        /**
         * Idempotency (Redis)
         */
        if ($paymentId = $redis->getPaymentByIdempotency($request->idempotency_key)) {
            $payment = Payment::findOrFail($paymentId);

            return response()->json([
                'id' => $payment->id,
                'status' => $payment->status,
            ]);
        }

        /**
         * Create payment
         */
        $payment = Payment::create([
            'id' => (string) Str::uuid(),
            'user_id' => $request->user_id,
            'gateway' => $request->gateway ?? 'ideal',
            'amount' => $request->amount,
            'currency' => 'EUR',
            'status' => 'pending',
            'idempotency_key' => $request->idempotency_key,
        ]);

        /**
         * Store idempotency key
         */
        $redis->storeIdempotency(
            $request->idempotency_key,
            $payment->id
        );

        /**
         * Cache initial status (for polling)
         */
        $redis->setPaymentStatus($payment->id, 'pending');

        /**
         * Dispatch async processing
         */
        ProcessPaymentJob::dispatch($payment->id);

        return response()->json([
            'id' => $payment->id,
            'status' => $payment->status,
        ], 201);
    }

    public function show(
        string $id,
        RedisPaymentService $redis
    ) {
        $payment = Payment::findOrFail($id);

        /**
         * Prefer Redis for hot path (polling)
         */
        $status = $redis->getPaymentStatus($payment->id)
            ?? $payment->status;

        return response()->json([
            'id' => $payment->id,
            'status' => $status,
            'amount' => $payment->amount,
            'failure_reason' => $payment->failure_reason,
            'checkout_url' => $payment->provider_checkout_url,
        ]);
    }
}
