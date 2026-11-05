<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\CrmLead;
use App\Models\DiscountApproval;
use App\Models\Quotation;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ── CRM KPIs ──────────────────────────────────────────────────────
        $totalLeads    = CrmLead::count();
        $activeLeads   = CrmLead::where('is_converted', false)->count();
        $opportunities = CrmLead::where('is_converted', true)
            ->whereNotIn('stage', ['Won', 'Lost'])->count();
        $wonOpps       = CrmLead::where('stage', 'Won')->count();
        $pipelineValue = CrmLead::where('is_converted', true)
            ->whereNotIn('stage', ['Won', 'Lost'])->sum('expected_revenue');

        // ── Sales KPIs ────────────────────────────────────────────────────
        $totalCustomers   = Contact::where('contact_type', 'Customer')->count();
        $totalQuotations  = Quotation::count();
        $draftQuotations  = Quotation::where('status', 'Draft')->count();
        $confirmedSO      = SalesOrder::where('status', 'Confirmed')->count();
        $totalSalesValue  = SalesOrder::whereIn('status', ['Confirmed', 'Done'])
            ->whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->sum('gross_amount');
        $pendingApprovals = DiscountApproval::where('status', 'Pending')->count();
        $overCreditLimit  = Contact::where('contact_type', 'Customer')
            ->where('credit_limit', '>', 0)->get()
            ->filter(fn($c) => $c->credit_exposure > (float)$c->credit_limit)->count();

        // ── Chart: Sales last 6 months ────────────────────────────────────
        $salesChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $val   = SalesOrder::whereIn('status', ['Confirmed', 'Done'])
                ->whereYear('order_date', $month->year)
                ->whereMonth('order_date', $month->month)
                ->sum('gross_amount');
            $salesChart[] = [
                'label' => $month->format('M Y'),
                'value' => (float) $val,
            ];
        }

        // ── Chart: Pipeline stage distribution ────────────────────────────
        $stages = CrmLead::$pipelineStages;
        $pipelineChart = collect($stages)->map(fn($s) => [
            'stage'   => $s,
            'count'   => CrmLead::where('stage', $s)->count(),
            'revenue' => (float) CrmLead::where('stage', $s)->sum('expected_revenue'),
        ])->values();

        // ── Pending actions ───────────────────────────────────────────────
        $pendingDiscounts = DiscountApproval::where('status', 'Pending')
            ->with([])
            ->orderByDesc('created_at')->limit(5)->get();

        $pendingQuotations = Quotation::whereIn('status', ['Draft', 'Sent'])
            ->with('customer')->orderBy('quotation_date')->limit(5)->get();

        $creditWarnings = Contact::where('contact_type', 'Customer')
            ->where('credit_limit', '>', 0)->get()
            ->filter(fn($c) => $c->credit_exposure >= ($c->credit_limit * 0.8))
            ->take(5);

        // ── Recent Activity ───────────────────────────────────────────────
        $recentLeads = CrmLead::with('customer')
            ->orderByDesc('created_at')->limit(6)->get();

        $recentSO = SalesOrder::with('customer')
            ->orderByDesc('created_at')->limit(6)->get();

        // ── Summary totals ────────────────────────────────────────────────
        $totalSalesAllTime = SalesOrder::whereIn('status', ['Confirmed', 'Done'])
            ->sum('gross_amount');

        $conversionRate = $totalLeads > 0
            ? round(($wonOpps / $totalLeads) * 100, 1) : 0;

        return view('dashboard.index', compact(
            // KPIs
            'totalLeads', 'activeLeads', 'opportunities', 'wonOpps', 'pipelineValue',
            'totalCustomers', 'totalQuotations', 'draftQuotations', 'confirmedSO',
            'totalSalesValue', 'totalSalesAllTime', 'conversionRate',
            'pendingApprovals', 'overCreditLimit',
            // Charts
            'salesChart', 'pipelineChart',
            // Activity
            'recentLeads', 'recentSO',
            // Actions
            'pendingDiscounts', 'pendingQuotations', 'creditWarnings'
        ));
    }
}
