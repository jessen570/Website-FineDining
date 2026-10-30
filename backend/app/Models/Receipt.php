<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_id',
        'purchase_order_id',
        'status',
        'receipt_date',
        'valuation_total',
        'is_posted',
    ];

    protected $casts = [
        'valuation_total' => 'decimal:2',
        'is_posted' => 'boolean',
        'receipt_date' => 'date',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'po_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ReceiptLine::class, 'receipt_id', 'receipt_id');
    }
}

