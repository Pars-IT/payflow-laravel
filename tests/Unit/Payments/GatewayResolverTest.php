<?php

namespace Tests\Unit\Payments;

use App\Models\Payment;
use App\Payments\GatewayResolver;
use App\Payments\Gateways\AbnAmroGateway;
use App\Payments\Gateways\IngGateway;
use App\Payments\Gateways\MollieGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GatewayResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_ing_gateway_resolved(): void
    {
        $payment = Payment::factory()->make(['gateway' => 'ing']);

        $gateway = (new GatewayResolver)->resolve($payment);

        $this->assertInstanceOf(IngGateway::class, $gateway);
    }

    public function test_abn_amro_gateway_resolved(): void
    {
        $payment = Payment::factory()->make(['gateway' => 'abn-amro']);

        $gateway = (new GatewayResolver)->resolve($payment);

        $this->assertInstanceOf(AbnAmroGateway::class, $gateway);
    }

    public function test_mollie_gateway_resolved(): void
    {
        $payment = Payment::factory()->make(['gateway' => 'mollie']);

        $gateway = (new GatewayResolver)->resolve($payment);

        $this->assertInstanceOf(MollieGateway::class, $gateway);
    }
}
