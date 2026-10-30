<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPricelist extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'variant_sku',
        'unit_purchase_price',
        'currency',
        'min_qty',
    ];

    protected $casts = [
        'unit_purchase_price' => 'decimal:2',
        'min_qty' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'variant_sku', 'variant_sku');
    }
}

