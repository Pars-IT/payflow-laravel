<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\Payments\PspException;
use PHPUnit\Framework\TestCase;

class PspExceptionsTest extends TestCase
{
    public function test_reason_is_returned(): void
    {
        $e = new PspException('psp_error');

        $this->assertEquals('psp_error', $e->reason());
    }
}
