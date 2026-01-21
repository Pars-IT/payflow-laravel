<?php

namespace Tests\Unit\Mail;

use App\Mail\PaymentStatusMail;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentStatusMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_status_mail_renders(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 1500,
        ]);

        $mail = new PaymentStatusMail($payment, 'success');

        $this->assertStringContainsString(
            'Hello Test User',
            $mail->render()
        );

    }
}
