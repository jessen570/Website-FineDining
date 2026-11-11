<?php

namespace App\Http\Controllers\Web\Sales;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\CreditValidation;
use App\Models\Invoice;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreditControlController extends Controller
{
    public function index(Request $request)
    {
        $allLimitedCustomers = Contact::where('contact_type', 'Customer')
            ->where('credit_limit', '>', 0)
            ->get();

        $summary = [
            'with_limit'      => $allLimitedCustomers->count(),
            'total_customers' => $allLimitedCustomers->count(),
            'over_limit'      => 0,
            'near_limit'      => 0,
        ];

        foreach ($allLimitedCustomers as $c) {
            $exp = $c->credit_exposure;
            $pct = $c->credit_limit > 0 ? ($exp / $c->credit_limit) * 100 : 0;
            if ($pct > 100) {
                $summary['over_limit']++;
            } elseif ($pct >= 80) {
                $summary['near_limit']++;
            }
        }

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

        return view('sales.credit.index', compact('customers', 'summary'));
    }

    public function show(string $id)
    {
        $customer = Contact::where(fn($q) => $q->where('contact_id', $id)->orWhere('id', $id))
            ->where('contact_type', 'Customer')
            ->firstOrFail();

        $check    = $customer->checkCreditLimit(0);
        $exposure = $check['current_exposure'];

        $openOrders = SalesOrder::where('customer_id', $customer->contact_id)
            ->where('status', 'Confirmed')
            ->where('invoice_status', '!=', 'Invoiced')
            ->orderByDesc('order_date')
            ->get();

        $unpaidInvoices = Invoice::where('partner_id', $customer->contact_id)
            ->where('type', 'Customer Invoice')
            ->whereIn('status', ['Draft', 'Posted'])
            ->where('amount_due', '>', 0)
            ->orderByDesc('invoice_date')
            ->get();

        $history = CreditValidation::where('customer_id', $customer->contact_id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('sales.credit.show', compact(
            'customer', 'exposure', 'check', 'openOrders', 'unpaidInvoices', 'history'
        ));
    }

    public function validate(Request $request)
    {
        $request->validate([
            'customer_id'      => 'required|string|exists:contacts,contact_id',
            'candidate_amount' => 'required|numeric|min:0',
            'document_type'    => 'required|string|max:50',
            'document_id'      => 'required|string|max:50',
        ]);

        $customer = Contact::where('contact_id', $request->customer_id)->firstOrFail();
        $check    = $customer->checkCreditLimit((float) $request->candidate_amount);

        $seq  = CreditValidation::max('id') + 1;
        $code = 'CV/' . now()->format('Y') . '/' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        CreditValidation::create([
            'validation_code'     => $code,
            'document_type'       => $request->document_type,
            'document_id'         => $request->document_id,
            'customer_id'         => $customer->contact_id,
            'credit_limit'        => $check['credit_limit'],
            'opening_ar'          => $check['opening_ar'],
            'unpaid_invoices'     => $check['unpaid_invoices'],
            'confirmed_so_amount' => $check['confirmed_so_amount'],
            'current_exposure'    => $check['current_exposure'],
            'candidate_amount'    => $check['candidate_amount'],
            'projected_exposure'  => $check['projected_exposure'],
            'passed'              => $check['passed'],
            'exceeded_by'         => $check['exceeded_by'],
            'validated_by'        => Auth::user()?->name ?? 'System',
        ]);

        AuditLog::log(
            'CREDIT_VALIDATION',
            'Contact',
            $customer->contact_id,
            $request->document_id,
            ['current_exposure' => $check['current_exposure'], 'credit_limit' => $check['credit_limit']],
            ['projected_exposure' => $check['projected_exposure'], 'passed' => $check['passed']],
            "Validasi kredit {$code} untuk {$customer->name}: " . ($check['passed'] ? 'LULUS' : 'MELAMPAUI LIMIT'),
            Auth::user()?->name ?? 'System',
            Auth::user()?->role ?? 'finance'
        );

        if ($request->wantsJson()) {
            return response()->json(array_merge($check, ['validated' => true, 'validation_code' => $code]));
        }

        $msg = $check['passed']
            ? "Validasi kredit ({$code}) LULUS. Sisa kredit setelah transaksi: Rp " . number_format(max(0, $check['credit_limit'] - $check['projected_exposure']), 0, ',', '.')
            : "Validasi kredit ({$code}) GAGAL. Melampaui limit sebesar Rp " . number_format($check['exceeded_by'], 0, ',', '.');

        return back()->with($check['passed'] ? 'success' : 'error', $msg);
    }
}
