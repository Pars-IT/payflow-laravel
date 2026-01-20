<?php

namespace App\Exceptions\Payments;

use RuntimeException;

class WalletNotFoundException extends RuntimeException
{
    protected $message = 'wallet_not_found';
}
