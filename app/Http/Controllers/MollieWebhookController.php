<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Exceptions\Payments\WalletNotFoundException;
use App\Models\Payment;
use App\Services\PaymentFinalizer;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mollie\Api\MollieApiClient;

class MollieWebhookController extends Controller
{
    public function handle(
        Request $request,
        PaymentFinalizer $finalizer,
        WalletService $walletService,
        MollieApiClient $mollieClient
    ) {
        Log::info('Mollie webhook called', ['payload' => $request->all()]);

        if (! $request->has('id')) {
            return response()->json(['error' => 'invalid_webhook'], 400);
        }

        $mollieClient->setApiKey(config('services.mollie.key'));

        // Mollie sends the payment id as "id"
        $molliePayment = $mollieClient->payments->get($request->id);

        $payment = Payment::where('provider', 'mollie')
            ->where('provider_payment_id', $molliePayment->id)
            ->firstOrFail();

        // Idempotency: do not process finalized payments again
        if (PaymentStatus::from($payment->status)->isFinal()) {
            return response()->json(['ok' => true]);
        }

        if ($molliePayment->isPaid()) {
            try {
                $walletService->creditFromPayment($payment);
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
