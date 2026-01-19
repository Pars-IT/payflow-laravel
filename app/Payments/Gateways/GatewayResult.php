<?php

namespace App\Payments\Gateways;

class GatewayResult
{
    public function __construct(
        public bool $success,
        public ?string $failureReason = null,
        public ?string $checkoutUrl = null,
        public bool $async = false
    ) {}
}
