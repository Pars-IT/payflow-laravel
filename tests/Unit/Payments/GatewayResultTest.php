<?php

namespace Tests\Unit\Payments;

use App\Payments\GatewayResult;
use PHPUnit\Framework\TestCase;

class GatewayResultTest extends TestCase
{
    public function test_sync_result(): void
    {
        $result = GatewayResult::success();

        $this->assertFalse($result->async);
        $this->assertNull($result->checkoutUrl);
    }

    public function test_async_result(): void
    {
        $result = GatewayResult::async(1, 'https://example.com');

        $this->assertTrue($result->async);
        $this->assertEquals('https://example.com', $result->checkoutUrl);
        $this->assertEquals(1, $result->providerPaymentId);
    }
}
