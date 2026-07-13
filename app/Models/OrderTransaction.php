<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'shopify_transaction_id',
        'kind',
        'status',
        'gateway',
        'amount',
        'processed_at',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
