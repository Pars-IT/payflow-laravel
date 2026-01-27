<?php

namespace Tests\Unit\Listeners;

use App\Enums\PaymentStatus;
use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Mail\PaymentStatusMail;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendPaymentEmailListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_sent_on_payment_success(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::Success->value,
        ]);

        PaymentSucceeded::dispatch($payment);

        Mail::assertSent(PaymentStatusMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_email_is_sent_on_payment_failure(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::Failed->value,
            'failure_reason' => 'psp_error',
        ]);

        PaymentFailed::dispatch($payment, 'psp_error');

        Mail::assertSent(PaymentStatusMail::class);
    }
}
