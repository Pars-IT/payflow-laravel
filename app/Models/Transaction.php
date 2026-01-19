<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'payment_id',
        'amount',
        'type',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];
}
