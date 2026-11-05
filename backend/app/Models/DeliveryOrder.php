<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'sales_order_id',
        'status',
        'delivery_date',
        'cost_of_goods_sold',
        'is_posted',
        'backorder_delivery_id',
    ];

    protected $casts = [
        'cost_of_goods_sold' => 'decimal:2',
        'is_posted' => 'boolean',
        'delivery_date' => 'date',
    ];

    public function getRouteKeyName(): string
    {
        return 'delivery_id';
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'order_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DeliveryOrderLine::class, 'delivery_id', 'delivery_id');
    }
}

