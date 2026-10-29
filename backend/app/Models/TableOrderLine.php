<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'variant_sku',
        'quantity',
        'unit_price',
        'subtotal',
        'pbjt_amount',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'pbjt_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function tableOrder(): BelongsTo
    {
        return $this->belongsTo(TableOrder::class, 'order_id', 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'variant_sku', 'variant_sku');
    }
}
