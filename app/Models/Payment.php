<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'gateway',
        'user_id',
        'amount',
        'currency',
        'status',
        'idempotency_key',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
