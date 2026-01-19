<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        // The gateway represents the logical payment method,
        'gateway',
        'user_id',
        'amount',
        'currency',
        'status',
        'idempotency_key',
        // The provider represents the external Payment Service Provider (PSP)
        'provider',
        'provider_payment_id',
        'provider_checkout_url',
        'failure_reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
