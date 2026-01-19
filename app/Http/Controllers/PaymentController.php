<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Jobs\ProcessPaymentJob;
use App\Payments\GatewayResolver;
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

        $resolver = new GatewayResolver();
        $gateway = $resolver->resolve($payment);
        $result = $gateway->charge($payment);

        // async gateway (Mollie)
        if ($result->async && $result->checkoutUrl) {
            return response()->json([
                'payment_id' => $payment->id,
                'redirect_url' => $result->checkoutUrl,
            ], 201);
        }

        // sync gateways
        ProcessPaymentJob::dispatch($payment->id);

        return response()->json($payment, 201);
    }

    public function show(string $id)
    {
        $payment = Payment::findOrFail($id);

        return response()->json([
            'id' => $payment->id,
            'status' => $payment->status,
            'amount' => $payment->amount,
        ]);
    }
}
