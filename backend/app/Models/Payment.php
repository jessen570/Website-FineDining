<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'invoice_id',
        'payment_type',
        'payment_method',
        'payment_date',
        'amount',
        'remaining_due',
        'is_posted',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'remaining_due' => 'decimal:2',
        'is_posted' => 'boolean',
        'payment_date' => 'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }
}

