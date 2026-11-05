<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'variant_sku',
        'name',
        'size',
        'color',
        'unit',
        'sales_price',
        'standard_cost',
        'default_vendor_id',
    ];

    protected $casts = [
        'sales_price' => 'decimal:2',
        'standard_cost' => 'decimal:2',
    ];

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class, 'variant_sku', 'variant_sku');
    }

    public function pricelists(): HasMany
    {
        return $this->hasMany(VendorPricelist::class, 'variant_sku', 'variant_sku');
    }

    public function getTotalOnHandAttribute(): float
    {
        return (float) $this->stockLevels()->sum('on_hand');
    }

    public function getTotalReservedAttribute(): float
    {
        return (float) $this->stockLevels()->sum('reserved');
    }

    public function getTotalAvailableAttribute(): float
    {
        return $this->total_on_hand - $this->total_reserved;
    }

    public function getRouteKeyName(): string
    {
        return 'variant_sku';
    }
}

