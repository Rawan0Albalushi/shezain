<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'gateway',
    'session_id',
    'client_reference',
    'amount_baisa',
    'status',
    'payload',
    'paid_at',
])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'amount_baisa' => 'integer',
            'payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
