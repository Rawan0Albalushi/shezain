<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'public_id',
    'number',
    'user_id',
    'customer_name',
    'phone',
    'email',
    'governorate_ar',
    'governorate_en',
    'city',
    'address_line',
    'notes',
    'subtotal',
    'shipping_fee',
    'total',
    'status',
    'stock_held',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'stock_held' => 'boolean',
            'subtotal' => 'integer',
            'shipping_fee' => 'integer',
            'total' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
