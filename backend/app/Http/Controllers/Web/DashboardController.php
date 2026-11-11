<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\CrmLead;
use App\Models\DiscountApproval;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\StockLevel;
use Illuminate\Support\Facades\Auth;
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

        // ── S-18 Operational & Inventory KPIs (F-22) ────────────────────────
        $totalStockUnits     = (int) StockLevel::sum('on_hand');
        $totalReservedUnits  = (int) StockLevel::sum('reserved');
        $totalAvailableUnits = max(0, $totalStockUnits - $totalReservedUnits);

        // Valuation = sum(on_hand * standard_cost)
        $stockLevels = StockLevel::with('product')->get();
        $totalStockValue = $stockLevels->sum(function ($sl) {
            $cost = $sl->product ? (float)$sl->product->standard_cost : 0;
            return (float)$sl->on_hand * $cost;
        });

        // AR exposure and AP obligations — include opening balances from Contacts (SR-19/T-35)
        $openingAR   = (float) \App\Models\Contact::where('contact_type', 'Customer')->sum('opening_ar');
        $openingAP   = (float) \App\Models\Contact::where('contact_type', 'Vendor')->sum('opening_ap');
        $invoiceAR   = (float) Invoice::where('type', 'Customer Invoice')->whereIn('status', ['Posted', 'Draft'])->sum('amount_due');
        $invoiceAP   = (float) Invoice::where('type', 'Vendor Bill')->whereIn('status', ['Posted', 'Draft'])->sum('amount_due');
        $totalAR     = round($openingAR + $invoiceAR, 2);
        $totalAP     = round($openingAP + $invoiceAP, 2);

        // RBAC Check for Financial Details (SR-14 / T-36)
        $currentUser = Auth::user();
        $userRole = strtolower($currentUser?->role ?? 'finance');
        // Hanya owner & finance yang dapat melihat rincian finansial AR/AP (SR-14)
        $canViewFinancials = in_array($userRole, ['owner', 'finance']);

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
            // S-18 Operational & Inventory KPIs
            'totalStockUnits', 'totalAvailableUnits', 'totalStockValue',
            'totalAR', 'totalAP', 'canViewFinancials',
            // Charts
            'salesChart', 'pipelineChart',
            // Activity
            'recentLeads', 'recentSO',
            // Actions
            'pendingDiscounts', 'pendingQuotations', 'creditWarnings'
        ));
    }
}
