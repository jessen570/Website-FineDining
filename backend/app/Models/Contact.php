<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'name',
        'contact_type',
        'customer_type',
        'city',
        'credit_limit',
        'payment_terms',
        'opening_ar',
        'opening_ap',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'opening_ar' => 'decimal:2',
        'opening_ap' => 'decimal:2',
    ];

    /**
     * Compute current credit exposure according to SR-15:
     * Exposure = Opening AR + Unpaid Invoices + Confirmed uninvoiced Sales Orders
     * Candidate order gross amount is evaluated against (credit_limit - current_exposure).
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
        $currentExposure = $this->credit_exposure;
        $totalWithCandidate = round($currentExposure + $candidateGross, 2);
        $limit = (float) $this->credit_limit;
        $passed = $totalWithCandidate <= $limit;

        return [
            'passed' => $passed,
            'credit_limit' => $limit,
            'current_exposure' => $currentExposure,
            'candidate_amount' => $candidateGross,
            'projected_exposure' => $totalWithCandidate,
            'exceeded_by' => $passed ? 0 : round($totalWithCandidate - $limit, 2),
        ];
    }
}

