<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'variant_sku',
        'ordered_qty',
        'delivered_qty',
        'invoiced_qty',
        'unit_price',
        'discount_percent',
        'subtotal',
        'pbjt_amount',
        'total_amount',
    ];

    protected $casts = [
        'ordered_qty' => 'decimal:2',
        'delivered_qty' => 'decimal:2',
        'invoiced_qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'pbjt_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'order_id', 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'variant_sku', 'variant_sku');
    }
}
