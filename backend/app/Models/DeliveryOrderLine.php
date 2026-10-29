<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'variant_sku',
        'ordered_qty',
        'delivered_qty',
        'backorder_qty',
        'standard_cost',
        'subtotal_cogs',
    ];

    protected $casts = [
        'ordered_qty' => 'decimal:2',
        'delivered_qty' => 'decimal:2',
        'backorder_qty' => 'decimal:2',
        'standard_cost' => 'decimal:2',
        'subtotal_cogs' => 'decimal:2',
    ];

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_id', 'delivery_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'variant_sku', 'variant_sku');
    }
}
