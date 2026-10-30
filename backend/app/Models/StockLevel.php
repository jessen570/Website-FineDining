<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_code',
        'variant_sku',
        'on_hand',
        'reserved',
    ];

    protected $casts = [
        'on_hand' => 'decimal:2',
        'reserved' => 'decimal:2',
    ];

    protected $appends = [
        'available',
    ];

    public function getAvailableAttribute(): float
    {
        return round((float) $this->on_hand - (float) $this->reserved, 2);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'variant_sku', 'variant_sku');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_code', 'location_code');
    }
}

