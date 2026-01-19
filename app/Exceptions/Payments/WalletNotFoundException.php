<?php

namespace App\Exceptions;

use RuntimeException;

class WalletNotFoundException extends RuntimeException
{
    protected $message = 'wallet_not_found';
}
