<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_id',
        'vendor_id',
        'status',
        'payment_terms',
        'order_date',
        'total_amount',
        'is_locked',
        'receipt_status',
        'billing_status',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'is_locked' => 'boolean',
        'order_date' => 'date',
    ];

    public function getRouteKeyName(): string
    {
        return 'po_id';
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'vendor_id', 'contact_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class, 'po_id', 'po_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class, 'purchase_order_id', 'po_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Invoice::class, 'source_doc_id', 'po_id')
            ->where('source_doc_type', 'Purchase Order');
    }
}

