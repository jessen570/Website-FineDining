<?php

namespace App\Http\Controllers\Web\Sales;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\CreditValidation;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreditControlController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::where('contact_type', 'Customer')
            ->where('credit_limit', '>', 0);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('contact_id', 'like', "%{$q}%");
            });
        }

        $customers = $query->orderBy('name')->paginate(20)->withQueryString();

        // Compute exposure for each (eager loaded)
        $summary = [
            'total_customers'    => $customers->total(),
            'over_limit'         => 0,
            'near_limit'         => 0,
        ];

        foreach ($customers as $c) {
            $exp = $c->credit_exposure;
            $pct = $c->credit_limit > 0 ? ($exp / $c->credit_limit) * 100 : 0;
            if ($pct > 100) $summary['over_limit']++;
            elseif ($pct >= 80) $summary['near_limit']++;
        }

        return view('sales.credit.index', compact('customers', 'summary'));
    }

    public function show(string $id)
    {
        $customer   = Contact::where(fn($q) => $q->where('contact_id', $id)->orWhere('id', $id))->where('contact_type', 'Customer')->firstOrFail();
        $exposure   = $customer->credit_exposure;
        $check      = $customer->checkCreditLimit(0);

        $openOrders = SalesOrder::where('customer_id', $customer->contact_id)
            ->whereIn('status', ['Confirmed'])
            ->where('invoice_status', '!=', 'Invoiced')
            ->get();

        $history = CreditValidation::where('customer_id', $customer->contact_id)
            ->orderByDesc('created_at')->limit(20)->get();

        return view('sales.credit.show', compact('customer', 'exposure', 'check', 'openOrders', 'history'));
    }

    public function validate(Request $request)
    {
        $request->validate([
            'customer_id'      => 'required|string|exists:contacts,contact_id',
            'candidate_amount' => 'required|numeric|min:0',
            'document_type'    => 'required|string',
            'document_id'      => 'required|string',
        ]);

        $customer = Contact::where('contact_id', $request->customer_id)->firstOrFail();
        $check    = $customer->checkCreditLimit((float) $request->candidate_amount);

        $seq = CreditValidation::max('id') + 1;
        CreditValidation::create([
            'validation_code'    => 'CV/' . now()->format('Y') . '/' . str_pad($seq, 4, '0', STR_PAD_LEFT),
            'document_type'      => $request->document_type,
            'document_id'        => $request->document_id,
            'customer_id'        => $customer->contact_id,
            'credit_limit'       => $check['credit_limit'],
            'opening_ar'         => $customer->opening_ar,
            'unpaid_invoices'    => 0,
            'confirmed_so_amount'=> $check['current_exposure'] - $customer->opening_ar,
            'current_exposure'   => $check['current_exposure'],
            'candidate_amount'   => $check['candidate_amount'],
            'projected_exposure' => $check['projected_exposure'],
            'passed'             => $check['passed'],
            'exceeded_by'        => $check['exceeded_by'],
            'validated_by'       => Auth::user()->name,
        ]);

        if (request()->wantsJson()) {
            return response()->json(array_merge($check, ['validated' => true]));
        }

        $msg = $check['passed']
            ? "Validasi kredit LULUS. Sisa kredit: Rp " . number_format($check['credit_limit'] - $check['projected_exposure'], 0, ',', '.')
            : "Validasi kredit GAGAL. Melampaui limit Rp " . number_format($check['exceeded_by'], 0, ',', '.');

        return back()->with($check['passed'] ? 'success' : 'error', $msg);
    }
}
