<?php

namespace App\Payments;

class GatewayResult
{
    public function __construct(
        public ?string $checkoutUrl = null,
        public bool $async = false
    ) {}
}
