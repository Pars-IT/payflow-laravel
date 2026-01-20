<?php

namespace App\Http\Controllers;

use App\Exceptions\Payments\WalletNotFoundException;
use App\Models\Payment;
use App\Services\PaymentFinalizer;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mollie\Api\MollieApiClient;

class MollieWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Mollie webhook called', ['payload' => $request->all()]);

        if (! $request->has('id')) {
            return response()->json(['error' => 'invalid_webhook'], 400);
        }

        $mollie = new MollieApiClient;
        $mollie->setApiKey(config('services.mollie.key'));

        // Mollie sends the payment id as "id"
        $molliePayment = $mollie->payments->get($request->id);

        $payment = Payment::where(
            'provider_payment_id',
            $molliePayment->id
        )->firstOrFail();

        // Idempotency: do not process finalized payments again
        if ($payment->status !== 'pending') {
            return response()->json(['ok' => true]);
        }

        $finalizer = app(PaymentFinalizer::class);

        if ($molliePayment->isPaid()) {
            try {
                app(WalletService::class)->creditFromPayment($payment);
                $finalizer->succeed($payment);

            } catch (WalletNotFoundException $e) {
                $finalizer->fail($payment, $e->getMessage());
            }

        } else {
            $reason = match ($molliePayment->status) {
                'canceled' => 'psp_canceled_by_user',
                'expired' => 'psp_expired',
                'failed' => 'psp_failed',
                default => 'psp_unknown',
            };

            $finalizer->fail($payment, $reason);
        }

        Log::info('Mollie webhook processed', ['payment_id' => $payment->id]);

        return response()->json(['ok' => true]);
    }
}
