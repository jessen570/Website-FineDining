<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CrmLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id', 'customer_id', 'name', 'company_name', 'contact_person',
        'email', 'phone', 'lead_source', 'stage', 'lead_status',
        'expected_revenue', 'probability', 'closing_date',
        'assigned_to', 'converted_opportunity_id', 'is_converted',
        'conversion_date', 'notes',
    ];

    protected $casts = [
        'expected_revenue' => 'decimal:2',
        'probability'      => 'decimal:2',
        'is_converted'     => 'boolean',
        'closing_date'     => 'date',
        'conversion_date'  => 'date',
    ];

    public static array $leadSources = [
        'Website', 'Referral', 'Walk-in', 'Event',
        'Social Media', 'Cold Call', 'Other',
    ];

    public static array $leadStatuses = [
        'New', 'Contacted', 'Qualified', 'Unqualified',
    ];

    public static array $pipelineStages = [
        'Lead', 'Qualified', 'Proposition', 'Negotiation', 'Won', 'Lost',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id', 'contact_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function linkedSalesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class, 'crm_lead_id', 'lead_id');
    }

    public function linkedQuotation(): HasOne
    {
        return $this->hasOne(Quotation::class, 'crm_lead_id', 'lead_id');
    }

    public function getRouteKeyName(): string
    {
        return 'lead_id';
    }
}
