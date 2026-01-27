<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Jobs\ProcessPaymentJob;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Services\RedisPaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(
        Request $request,
        PaymentRepositoryInterface $paymentRepository
    ) {
        $request->validate([
            'user_id' => 'required|integer',
            'amount' => 'required|integer|min:1',
            'idempotency_key' => 'required|string',
        ]);

        // DB-level idempotency (safe fallback)
        $existing = $paymentRepository->findByIdempotencyKey($request->idempotency_key);
        if ($existing) {
            return response()->json([
                'id' => $existing->id,
                'status' => $existing->status,
            ], 200);
        }

        $payment = $paymentRepository->createPending([
            'user_id' => $request->user_id,
            'gateway' => $request->gateway ?? 'ideal',
            'amount' => $request->amount,
            'idempotency_key' => $request->idempotency_key,
        ]);

        ProcessPaymentJob::dispatch($payment->id);

        return response()->json([
            'id' => $payment->id,
            'status' => $payment->status,
        ], 201);
    }

    public function show(
        string $id,
        RedisPaymentService $redisPaymentService,
        PaymentRepositoryInterface $paymentRepository
    ) {
        /**
         * Redis hot path
         */
        if ($state = $redisPaymentService->getPaymentState($id)) {
            return response()->json(array_merge(
                ['cached' => true, 'id' => $id],
                $state
            ));
        }

        /**
         * DB source of truth
         */
        $payment = $paymentRepository->findById($id);

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
            $paymentRepository->markTimedOut($payment);
        }

        /**
         * Warm Redis cache
         */
        if (
            PaymentStatus::from($payment->status)->isFinal()
            || $payment->provider_checkout_url !== null
        ) {
            $redisPaymentService->setPaymentState($payment->id, [
                'status' => $payment->status,
                'failure_reason' => $payment->failure_reason,
                'checkout_url' => $payment->provider_checkout_url,
            ]);
        }

        return response()->json($response);
    }
}
