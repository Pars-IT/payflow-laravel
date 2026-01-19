<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPaymentJob;
use App\Models\Payment;
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

        $existing = Payment::where('idempotency_key', $request->idempotency_key)->first();
        if ($existing) {
            return response()->json($existing);
        }

        $payment = Payment::create([
            'id' => (string) Str::uuid(),
            'user_id' => $request->user_id,
            'gateway' => $request->gateway ?? 'ideal',
            'amount' => $request->amount,
            'currency' => 'EUR',
            'status' => 'pending',
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
        $payment = Payment::findOrFail($id);

        return response()->json([
            'id' => $payment->id,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'failure_reason' => $payment->failure_reason,
            'checkout_url' => $payment->provider_checkout_url,
        ]);
    }
}
