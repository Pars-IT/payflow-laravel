<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        /**
         * Verify signature
         */
        $payload = $request->all();
        $signature = $request->header('X-Signature');

        $expectedSignature = hash_hmac(
            'sha256',
            json_encode($payload),
            config('services.webhook.secret')
        );

        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('Invalid webhook signature', $payload);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        /**
         * Idempotency check
         */
        $eventId = $payload['payment_id'] . '-' . $payload['status'];

        if (
            DB::table('webhook_calls')
                ->where('event_id', $eventId)
                ->exists()
        ) {
            return response()->json(['ok' => true]); // already processed
        }

        /**
         * Process webhook atomically
         */
        DB::transaction(function () use ($eventId, $payload) {

            // mark webhook as processed
            DB::table('webhook_calls')->insert([
                'event_id'   => $eventId,
                'created_at' => now(),
            ]);

            // update payment status (SOURCE OF TRUTH)
            $payment = Payment::findOrFail($payload['payment_id']);

            $payment->status = $payload['status']; // success | failed
            $payment->save();
        });

        Log::info('Webhook processed successfully', $payload);

        return response()->json(['ok' => true]);
    }
}
