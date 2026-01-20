<?php

namespace App\Payments;

class GatewayResult
{
    private function __construct(
        public bool $success,
        public ?string $failureReason = null,
        public ?string $checkoutUrl = null,
        public bool $async = false
    ) {}

    public static function success(): self
    {
        return new self(success: true);
    }

    public static function failed(string $reason): self
    {
        return new self(success: false, failureReason: $reason);
    }

    public static function async(string $checkoutUrl): self
    {
        return new self(
            success: true,
            checkoutUrl: $checkoutUrl,
            async: true
        );
    }
}
