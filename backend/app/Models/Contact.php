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
     * SR-15: Exposure breakdown = Opening AR + Unpaid Invoices + Confirmed uninvoiced SOs
     */
    public function getCreditBreakdownAttribute(): array
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

        $total = round($opening + $unpaidInvoices + $confirmedUninvoicedOrders, 2);

        return [
            'opening_ar'          => round($opening, 2),
            'unpaid_invoices'     => round($unpaidInvoices, 2),
            'confirmed_so_amount' => round($confirmedUninvoicedOrders, 2),
            'total_exposure'      => $total,
        ];
    }

    /**
     * SR-15: Exposure = Opening AR + Unpaid Invoices + Confirmed uninvoiced SOs
     */
    public function getCreditExposureAttribute(): float
    {
        return $this->credit_breakdown['total_exposure'];
    }

    public function checkCreditLimit(float $candidateGross): array
    {
        $breakdown          = $this->credit_breakdown;
        $currentExposure    = $breakdown['total_exposure'];
        $totalWithCandidate = round($currentExposure + $candidateGross, 2);
        $limit              = (float) $this->credit_limit;
        $passed             = $limit <= 0 || $totalWithCandidate <= $limit;

        return [
            'passed'              => $passed,
            'credit_limit'        => $limit,
            'opening_ar'          => $breakdown['opening_ar'],
            'unpaid_invoices'     => $breakdown['unpaid_invoices'],
            'confirmed_so_amount' => $breakdown['confirmed_so_amount'],
            'current_exposure'    => $currentExposure,
            'candidate_amount'    => $candidateGross,
            'projected_exposure'  => $totalWithCandidate,
            'exceeded_by'         => $passed ? 0 : round($totalWithCandidate - $limit, 2),
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'contact_id';
    }
}
