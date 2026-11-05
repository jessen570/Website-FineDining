<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'customer_id', 'crm_lead_id', 'quotation_id', 'salesperson_id',
        'status', 'payment_terms', 'order_date', 'confirmation_date',
        'discount_percent', 'approval_id',
        'subtotal', 'pbjt_amount', 'gross_amount',
        'is_locked', 'delivery_status', 'invoice_status',
        'notes', 'terms_conditions',
    ];

    protected $casts = [
        'discount_percent'  => 'decimal:2',
        'subtotal'          => 'decimal:2',
        'pbjt_amount'       => 'decimal:2',
        'gross_amount'      => 'decimal:2',
        'is_locked'         => 'boolean',
        'order_date'        => 'date',
        'confirmation_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id', 'contact_id');
    }

    public function crmLead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id', 'lead_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'quotation_id', 'quotation_id');
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class, 'order_id', 'order_id');
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'sales_order_id', 'order_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'source_doc_id', 'order_id')
            ->where('source_doc_type', 'Sales Order');
    }

    public function getRouteKeyName(): string
    {
        return 'order_id';
    }
}
