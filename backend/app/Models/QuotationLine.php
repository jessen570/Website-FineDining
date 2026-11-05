<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id', 'variant_sku', 'quantity', 'unit_price',
        'discount_percent', 'subtotal', 'pbjt_rate', 'pbjt_amount', 'total_amount',
    ];

    protected $casts = [
        'quantity'         => 'decimal:2',
        'unit_price'       => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'subtotal'         => 'decimal:2',
        'pbjt_rate'        => 'decimal:4',
        'pbjt_amount'      => 'decimal:2',
        'total_amount'     => 'decimal:2',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'quotation_id', 'quotation_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'variant_sku', 'variant_sku');
    }
}
