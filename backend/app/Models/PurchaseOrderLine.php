<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_id',
        'variant_sku',
        'ordered_qty',
        'received_qty',
        'accepted_qty',
        'billed_qty',
        'unit_purchase_price',
        'subtotal',
    ];

    protected $casts = [
        'ordered_qty' => 'decimal:2',
        'received_qty' => 'decimal:2',
        'accepted_qty' => 'decimal:2',
        'billed_qty' => 'decimal:2',
        'unit_purchase_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id', 'po_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'variant_sku', 'variant_sku');
    }
}

