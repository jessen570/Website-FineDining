<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id', 'customer_id', 'crm_lead_id', 'salesperson_id',
        'quotation_date', 'expiration_date', 'discount_percent', 'approval_id',
        'subtotal', 'pbjt_amount', 'gross_amount', 'status',
        'notes', 'terms_conditions', 'is_locked',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'subtotal'         => 'decimal:2',
        'pbjt_amount'      => 'decimal:2',
        'gross_amount'     => 'decimal:2',
        'is_locked'        => 'boolean',
        'quotation_date'   => 'date',
        'expiration_date'  => 'date',
    ];

    public static array $statuses = [
        'Draft', 'Sent', 'Accepted', 'Rejected', 'Expired', 'Converted',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id', 'contact_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id', 'lead_id');
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuotationLine::class, 'quotation_id', 'quotation_id');
    }

    public function salesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class, 'quotation_id', 'quotation_id');
    }

    public function getRouteKeyName(): string
    {
        return 'quotation_id';
    }
}
