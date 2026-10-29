<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnOrder extends Model
{
    use HasFactory;

    protected $table = 'returns';

    protected $fillable = [
        'return_id',
        'original_invoice_id',
        'customer_id',
        'status',
        'variant_sku',
        'quantity',
        'credit_subtotal',
        'pbjt_reversal',
        'total_credit_note',
        'cogs_reversal',
        'credit_note_id',
        'is_posted',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'credit_subtotal' => 'decimal:2',
        'pbjt_reversal' => 'decimal:2',
        'total_credit_note' => 'decimal:2',
        'cogs_reversal' => 'decimal:2',
        'is_posted' => 'boolean',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'original_invoice_id', 'invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'variant_sku', 'variant_sku');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id', 'contact_id');
    }
}
