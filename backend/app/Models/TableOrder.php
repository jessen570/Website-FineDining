<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TableOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'booking_id',
        'table_id',
        'server_id',
        'customer_id',
        'status',
        'subtotal',
        'pbjt_amount',
        'gross_amount',
        'split_count',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'pbjt_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
    ];

    public function getRouteKeyName(): string
    {
        return 'order_id';
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id', 'table_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'server_id', 'employee_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TableOrderLine::class, 'order_id', 'order_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'source_doc_id', 'order_id')
            ->where('source_doc_type', 'Table Order');
    }
}

