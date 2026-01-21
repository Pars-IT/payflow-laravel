<?php

namespace Tests\Unit\Payments\Gateways;

use App\Models\Payment;
use App\Payments\Gateways\AbnAmroGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbnAmroGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_abn_amro_rejects_odd_amount(): void
    {
        $payment = Payment::factory()->make([
            'amount' => 1501,
        ]);

        $gateway = new AbnAmroGateway;
        $result = $gateway->charge($payment);

        $this->assertFalse($result->success);
        $this->assertEquals('abn_amro_rejected', $result->failureReason);
    }

    public function test_abn_amro_accepts_even_amount(): void
    {
        $payment = Payment::factory()->make([
            'amount' => 1500,
        ]);

        $gateway = new AbnAmroGateway;
        $result = $gateway->charge($payment);

        $this->assertTrue($result->success);
        $this->assertFalse($result->async);
    }
}
