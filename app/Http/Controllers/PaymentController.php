<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Jobs\ProcessPaymentJob;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Services\RedisPaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private RedisPaymentService $redisPaymentService,
    ) {
        //
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'amount' => 'required|integer|min:1',
            'idempotency_key' => 'required|string',
        ]);

        // idempotency (safe fallback)
        $existing = $this->paymentRepository->findByIdempotencyKey($request->idempotency_key);
        if ($existing) {
            return $this->show((string) $existing->id);
        }

        $payment = $this->paymentRepository->createPending([
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

    public function show(string $id)
    {
        if ($state = $this->redisPaymentService->getPaymentState($id)) {
            return response()->json(array_merge(
                ['cached' => true, 'id' => $id],
                $state
            ));
        }

        $payment = $this->paymentRepository->findById($id);

        if (PaymentStatus::from($payment->status)->isPending() &&
            $payment->provider_checkout_url === null &&
            $payment->created_at->lt(now()->subMinutes(1))
        ) {
            // auto-heal
            $this->paymentRepository->markTimedOut($payment);
        }

        $response = [
            'id' => $payment->id,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'failure_reason' => $payment->failure_reason,
            'checkout_url' => $payment->provider_checkout_url,
        ];

        if (PaymentStatus::from($payment->status)->isFinal()
            || $payment->provider_checkout_url !== null
        ) {
            // Warm Redis cache
            $this->redisPaymentService->setPaymentState($payment->id, $response);
        }

        return response()->json($response);
    }
}
