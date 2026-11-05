<?php

namespace App\Http\Controllers\Web\Sales;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\CrmLead;
use App\Models\Quotation;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::where('contact_type', 'Customer');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('contact_id', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%");
            });
        }
        if ($request->filled('type')) {
            $query->where('customer_type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $customers = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('sales.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('sales.customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contact_id'    => 'required|string|unique:contacts,contact_id',
            'name'          => 'required|string|max:255',
            'customer_type' => 'nullable|in:Individu,Perusahaan',
            'contact_person'=> 'nullable|string|max:255',
            'phone'         => 'nullable|string|max:50',
            'email'         => 'nullable|email|max:255',
            'city'          => 'nullable|string|max:100',
            'address'       => 'nullable|string',
            'tax_id'        => 'nullable|string|max:50',
            'credit_limit'  => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|max:100',
            'opening_ar'    => 'nullable|numeric|min:0',
            'status'        => 'nullable|in:active,inactive',
        ]);

        $validated['contact_type'] = 'Customer';
        $validated['opening_ap']   = 0;
        $validated['city']         = $validated['city'] ?? '-';
        $validated['status']       = $validated['status'] ?? 'active';

        $contact = Contact::create($validated);

        AuditLog::log('CREATE', 'Contact', $contact->contact_id, null, null, $validated,
            "Customer {$contact->name} dibuat.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('sales.customers.show', $contact->contact_id)
            ->with('success', "Customer {$contact->name} berhasil ditambahkan.");
    }

    public function show(string $id)
    {
        $customer = Contact::where(function ($q) use ($id) {
                $q->where('contact_id', $id)->orWhere('id', $id);
            })
            ->where('contact_type', 'Customer')->firstOrFail();

        $exposure   = $customer->credit_exposure;
        $quotations = Quotation::with('lines')->where('customer_id', $customer->contact_id)
            ->orderByDesc('quotation_date')->limit(10)->get();
        $orders     = SalesOrder::where('customer_id', $customer->contact_id)
            ->orderByDesc('order_date')->limit(10)->get();
        $leads      = CrmLead::with('assignedUser')->where('customer_id', $customer->contact_id)
            ->orderByDesc('created_at')->limit(5)->get();

        return view('sales.customers.show', compact('customer', 'exposure', 'quotations', 'orders', 'leads'));
    }

    public function edit(string $id)
    {
        $customer = Contact::where(function ($q) use ($id) {
            $q->where('contact_id', $id)->orWhere('id', $id);
        })->firstOrFail();
        return view('sales.customers.edit', compact('customer'));
    }

    public function update(Request $request, string $id)
    {
        $customer = Contact::where(function ($q) use ($id) {
            $q->where('contact_id', $id)->orWhere('id', $id);
        })->firstOrFail();

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'customer_type' => 'nullable|in:Individu,Perusahaan',
            'phone'         => 'nullable|string|max:50',
            'email'         => 'nullable|email|max:255',
            'city'          => 'nullable|string|max:100',
            'address'       => 'nullable|string',
            'tax_id'        => 'nullable|string|max:50',
            'credit_limit'  => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|max:100',
            'opening_ar'    => 'nullable|numeric|min:0',
            'status'        => 'nullable|in:active,inactive',
        ]);

        $before = $customer->toArray();
        $customer->update($validated);

        AuditLog::log('UPDATE', 'Contact', $customer->contact_id, null, $before, $validated,
            "Customer {$customer->name} diperbarui.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('sales.customers.show', $customer->contact_id)
            ->with('success', 'Data customer berhasil diperbarui.');
    }

    public function creditDetail(string $id)
    {
        $customer   = Contact::where(function ($q) use ($id) {
                $q->where('contact_id', $id)->orWhere('id', $id);
            })->where('contact_type', 'Customer')->firstOrFail();
        $exposure   = $customer->credit_exposure;
        $check      = $customer->checkCreditLimit(0);
        $orders     = SalesOrder::where('customer_id', $customer->contact_id)
            ->whereIn('status', ['Confirmed'])
            ->where('invoice_status', '!=', 'Invoiced')
            ->get();

        return view('sales.customers.credit', compact('customer', 'exposure', 'check', 'orders'));
    }
}
