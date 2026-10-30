<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_id',
        'variant_sku',
        'ordered_qty',
        'received_qty',
        'accepted_qty',
        'rejected_qty',
        'unit_purchase_price',
        'standard_cost',
        'valuation_amount',
    ];

    protected $casts = [
        'ordered_qty' => 'decimal:2',
        'received_qty' => 'decimal:2',
        'accepted_qty' => 'decimal:2',
        'rejected_qty' => 'decimal:2',
        'unit_purchase_price' => 'decimal:2',
        'standard_cost' => 'decimal:2',
        'valuation_amount' => 'decimal:2',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class, 'receipt_id', 'receipt_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'variant_sku', 'variant_sku');
    }
}

