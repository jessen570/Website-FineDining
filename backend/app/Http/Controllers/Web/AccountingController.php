<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function index()
    {
        // Balance Sheet summary from journal items
        $accounts = ChartOfAccount::all();
        $balances = [];

        foreach ($accounts as $acc) {
            $debit  = JournalItem::where('account_code', $acc->account_code)->sum('debit');
            $credit = JournalItem::where('account_code', $acc->account_code)->sum('credit');
            $balance = match($acc->type) {
                'Aset', 'Beban', 'Kontra Pendapatan' => $debit - $credit,
                default => $credit - $debit,
            };
            $balances[] = [
                'account'   => $acc,
                'debit'     => $debit,
                'credit'    => $credit,
                'balance'   => $balance,
            ];
        }

        $recentEntries = JournalEntry::orderByDesc('entry_date')->orderByDesc('id')->limit(10)->get();

        // Convert balances array to collection of objects for the view ($accounts variable expected)
        $accounts = collect($balances)->map(function($b) {
            $obj = $b['account'];
            // Add computed balance as attribute
            $obj->setAttribute('account_type', $obj->type); // alias for view compatibility
            $obj->total_debit   = $b['debit'];
            $obj->total_credit  = $b['credit'];
            $obj->balance       = $b['balance'];
            return $obj;
        });

        return view('accounting.index', compact('accounts', 'balances', 'recentEntries'));
    }

    public function ledger(Request $request)
    {
        $accounts     = ChartOfAccount::orderBy('account_code')->get();
        $selectedCode = $request->get('account_code', '1100');

        $items = JournalItem::with('entry')
            ->where('account_code', $selectedCode)
            ->orderBy('created_at')
            ->paginate(30)->withQueryString();

        $runningBalance = 0;
        $account = ChartOfAccount::where('account_code', $selectedCode)->first();

        return view('accounting.ledger', compact('accounts', 'selectedCode', 'items', 'account'));
    }

    public function entries(Request $request)
    {
        $query = JournalEntry::query();

        if ($request->filled('ref_type')) {
            $query->where('reference_type', $request->ref_type);
        }
        if ($request->filled('date_from')) {
            $query->where('entry_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('entry_date', '<=', $request->date_to);
        }

        $entries = $query->orderByDesc('entry_date')->orderByDesc('id')->paginate(20)->withQueryString();
        $refTypes = ['Opening', 'Delivery Order', 'Customer Invoice', 'Receipt', 'Vendor Bill', 'Payment', 'Return', 'Credit Note'];

        return view('accounting.entries', compact('entries', 'refTypes'));
    }

    public function showEntry(string $id)
    {
        $entry = JournalEntry::with('items')->where('entry_id', $id)->firstOrFail();
        return view('accounting.entry_show', compact('entry'));
    }
}
