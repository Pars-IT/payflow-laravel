<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Exceptions\Payments\WalletNotFoundException;
use App\Repositories\Contracts\PaymentRepositoryInterface;
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
        MollieApiClient $mollieClient,
        PaymentRepositoryInterface $paymentRepository
    ) {
        Log::info('Mollie webhook called', ['id' => $request->id]);

        if (! $request->has('id')) {
            return response()->json(['error' => 'invalid_webhook'], 400);
        }

        $mollieClient->setApiKey(config('services.mollie.key'));

        try {
            // Mollie sends the payment id as "id"
            $molliePayment = $mollieClient->payments->get($request->id);
        } catch (\Exception $e) {
            Log::error('Mollie API error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['ok' => true]);
        }

        $payment = $paymentRepository->findByProviderPaymentId('mollie', $molliePayment->id);

        if (! $payment) {
            Log::warning('Payment not found for webhook', [
                'provider_id' => $molliePayment->id,
            ]);

            return response()->json(['ok' => true]);
        }

        // Idempotency: do not process finalized payments again
        if (PaymentStatus::from($payment->status)->isFinal()) {
            return response()->json(['ok' => true]);
        }

        if ((int) round($molliePayment->amount->value * 100) !== $payment->amount) {
            Log::error('Amount mismatch', [
                'mollie' => $molliePayment->amount->value,
                'local' => $payment->amount,
            ]);

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
