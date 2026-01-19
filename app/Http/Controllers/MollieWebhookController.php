<?php

namespace App\Http\Controllers;

use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Models\Payment;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mollie\Api\MollieApiClient;

class MollieWebhookController extends Controller
{
    public function handle(Request $request)
    {

        Log::info('Mollie Webhook called', ['payload' => $request->all()]);
        if (! $request->has('id')) {
            return response()->json(['error' => 'Invalid webhook'], 400);
        }

        $mollie = new MollieApiClient;
        $mollie->setApiKey(config('services.mollie.key'));

        // Mollie sends the payment id as "id"
        $molliePayment = $mollie->payments->get($request->id);

        $payment = Payment::where(
            'provider_payment_id',
            $molliePayment->id
        )->firstOrFail();

        if (
            (int) round($molliePayment->amount->value * 100) !== $payment->amount ||
            $molliePayment->amount->currency !== $payment->currency
        ) {
            abort(400, 'Amount mismatch');
        }

        // Idempotency: do not process finalized payments again
        if ($payment->status !== 'pending') {
            return response()->json(['ok' => true]);
        }

        DB::transaction(function () use ($payment, $molliePayment) {

            if ($molliePayment->isPaid()) {
                try {
                    app(WalletService::class)->creditFromPayment($payment);

                    $payment->status = 'success';
                    $payment->save();

                    event(new PaymentSucceeded($payment));
                } catch (\RuntimeException $e) {
                    $payment->status = 'failed';
                    $payment->save();

                    event(new PaymentFailed($payment, $e->getMessage()));
                }
            } else {
                $payment->status = 'failed';
                $payment->save();

                event(new PaymentFailed(
                    $payment,
                    $molliePayment->status
                ));
            }
        });

        Log::info('Mollie Webhook processed successfully');

        return response()->json(['ok' => true]);
    }
}
