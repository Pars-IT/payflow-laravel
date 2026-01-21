<?php

namespace App\Exceptions\Payments;

use RuntimeException;

/**
 * General PSP (Payment Service Provider) exception
 */
class PspException extends RuntimeException
{
    public function __construct(
        private string $reason = 'psp_error'
    ) {
        parent::__construct($reason);
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
