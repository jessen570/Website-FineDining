<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id', 'name', 'contact_type', 'customer_type',
        'city', 'phone', 'email', 'address', 'tax_id', 'status',
        'credit_limit', 'payment_terms', 'opening_ar', 'opening_ap',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'opening_ar'   => 'decimal:2',
        'opening_ap'   => 'decimal:2',
    ];

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'customer_id', 'contact_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'customer_id', 'contact_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(CrmLead::class, 'customer_id', 'contact_id');
    }

    /**
     * SR-15: Exposure = Opening AR + Unpaid Invoices + Confirmed uninvoiced SOs
     */
    public function getCreditExposureAttribute(): float
    {
        $opening = (float) $this->opening_ar;

        $unpaidInvoices = (float) Invoice::where('partner_id', $this->contact_id)
            ->where('type', 'Customer Invoice')
            ->whereIn('status', ['Draft', 'Posted'])
            ->sum('amount_due');

        $confirmedUninvoicedOrders = (float) SalesOrder::where('customer_id', $this->contact_id)
            ->where('status', 'Confirmed')
            ->where('invoice_status', '!=', 'Invoiced')
            ->sum('gross_amount');

        return round($opening + $unpaidInvoices + $confirmedUninvoicedOrders, 2);
    }

    public function checkCreditLimit(float $candidateGross): array
    {
        $currentExposure    = $this->credit_exposure;
        $totalWithCandidate = round($currentExposure + $candidateGross, 2);
        $limit              = (float) $this->credit_limit;
        $passed             = $totalWithCandidate <= $limit;

        return [
            'passed'             => $passed,
            'credit_limit'       => $limit,
            'current_exposure'   => $currentExposure,
            'candidate_amount'   => $candidateGross,
            'projected_exposure' => $totalWithCandidate,
            'exceeded_by'        => $passed ? 0 : round($totalWithCandidate - $limit, 2),
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'contact_id';
    }
}
