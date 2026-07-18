<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'ai_order_id',
    'order_number',
    'user_id',
    'customer_id',
    'vendor_id',
    'total_amount',
    'currency',
    'items',
    'shipping_address',
    'shipping_name',
    'shipping_phone',
    'tracking_number',
    'payment_method',
    'purchase_date',
    'status',
])]
class Order extends Model
{
    protected $casts = [
        'items' => 'array',
        'purchase_date' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
