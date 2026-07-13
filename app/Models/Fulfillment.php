<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fulfillment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'shopify_fulfillment_id',
        'status',
        'tracking_company',
        'tracking_number',
        'tracking_url',
        'shopify_updated_at',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'shopify_updated_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
