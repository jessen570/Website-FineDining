<?php

namespace App\Http\Controllers\Web\Sales;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Quotation;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo   = $request->date_to   ?? now()->toDateString();

        // ── KPIs ──────────────────────────────────────────────────────────
        $totalQuotations = Quotation::whereBetween('quotation_date', [$dateFrom, $dateTo])->count();
        $totalSO         = SalesOrder::whereBetween('order_date', [$dateFrom, $dateTo])->count();
        $confirmedSO     = SalesOrder::where('status', 'Confirmed')
            ->whereBetween('order_date', [$dateFrom, $dateTo])->count();
        $totalSalesValue = SalesOrder::whereIn('status', ['Confirmed', 'Done'])
            ->whereBetween('order_date', [$dateFrom, $dateTo])->sum('gross_amount');
        $pendingQuotations = Quotation::whereIn('status', ['Draft', 'Sent'])->count();
        $conversionRate    = $totalQuotations > 0
            ? round(($confirmedSO / $totalQuotations) * 100, 1) : 0;

        // ── By Customer ──────────────────────────────────────────────────
        $byCustomer = SalesOrder::with('customer')
            ->whereIn('status', ['Confirmed', 'Done'])
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->selectRaw('customer_id, COUNT(*) as order_count, SUM(gross_amount) as total')
            ->groupBy('customer_id')
            ->orderByDesc('total')
            ->limit(10)->get();

        // ── By Month (last 6 months) ──────────────────────────────────────
        $byMonth = SalesOrder::whereIn('status', ['Confirmed', 'Done'])
            ->where('order_date', '>=', now()->subMonths(6)->toDateString())
            ->selectRaw("DATE_FORMAT(order_date, '%Y-%m') as month, COUNT(*) as count, SUM(gross_amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // ── Recent SOs ────────────────────────────────────────────────────
        $recentSO = SalesOrder::with('customer')
            ->orderByDesc('order_date')->orderByDesc('id')->limit(10)->get();

        // ── Pending Quotations ────────────────────────────────────────────
        $pendingQ = Quotation::with('customer')
            ->whereIn('status', ['Draft', 'Sent'])
            ->orderByDesc('quotation_date')->limit(10)->get();

        return view('sales.reports.index', compact(
            'totalQuotations', 'totalSO', 'confirmedSO', 'totalSalesValue',
            'pendingQuotations', 'conversionRate', 'byCustomer', 'byMonth',
            'recentSO', 'pendingQ', 'dateFrom', 'dateTo'
        ));
    }
}
