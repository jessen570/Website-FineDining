<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'type',
        'partner_id',
        'source_doc_type',
        'source_doc_id',
        'linked_delivery_id',
        'linked_receipt_id',
        'invoice_date',
        'status',
        'subtotal',
        'pbjt_amount',
        'price_variance_amount',
        'total_amount',
        'amount_paid',
        'amount_due',
        'is_posted',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'pbjt_amount' => 'decimal:2',
        'price_variance_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'is_posted' => 'boolean',
        'invoice_date' => 'date',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'partner_id', 'contact_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class, 'invoice_id', 'invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id', 'invoice_id');
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class, 'linked_delivery_id', 'delivery_id');
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class, 'linked_receipt_id', 'receipt_id');
    }
}

