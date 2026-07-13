<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'customer_id',
        'shopify_order_id',
        'name',
        'email',
        'phone',
        'currency',
        'total_price',
        'financial_status',
        'fulfillment_status',
        'shipping_address',
        'billing_address',
        'processed_at',
        'cancelled_at',
        'closed_at',
        'shopify_updated_at',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'processed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'closed_at' => 'datetime',
            'shopify_updated_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shop_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(OrderLineItem::class);
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(Fulfillment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(OrderTransaction::class);
    }
}
