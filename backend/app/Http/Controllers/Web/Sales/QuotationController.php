<?php

namespace App\Http\Controllers\Web\Sales;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\CrmLead;
use App\Models\DiscountApproval;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationLine;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    const PBJT_RATE = 0.10;

    public function index(Request $request)
    {
        $query = Quotation::with('customer');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('quotation_id', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$q}%"));
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->where('quotation_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('quotation_date', '<=', $request->date_to);
        }

        $quotations = $query->orderByDesc('quotation_date')->orderByDesc('id')->paginate(20)->withQueryString();
        $statuses   = Quotation::$statuses;

        return view('sales.quotations.index', compact('quotations', 'statuses'));
    }

    public function create(Request $request)
    {
        $customers  = Contact::where('contact_type', 'Customer')->orderBy('name')->get();
        $products   = Product::orderBy('variant_sku')->get();
        $salesUsers = User::whereIn('role', ['owner', 'finance', 'server'])->orderBy('name')->get();
        $leads      = CrmLead::where('is_converted', true)->orderByDesc('id')->get();
        $preCustomer = $request->customer_id;
        $fromLead    = $request->from_lead ? CrmLead::where('lead_id', $request->from_lead)->first() : null;

        return view('sales.quotations.create', compact(
            'customers', 'products', 'salesUsers', 'leads', 'preCustomer', 'fromLead'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id'      => 'required|string|exists:contacts,contact_id',
            'quotation_date'   => 'required|date',
            'expiration_date'  => 'nullable|date|after_or_equal:quotation_date',
            'crm_lead_id'      => 'nullable|string|exists:crm_leads,lead_id',
            'salesperson_id'   => 'nullable|exists:users,id',
            'discount_percent' => 'required|numeric|min:0|max:15',
            'notes'            => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'lines'            => 'required|array|min:1',
            'lines.*.variant_sku' => 'required|string|exists:products,variant_sku',
            'lines.*.quantity'    => 'required|numeric|min:1',
            'lines.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $discount = (float) $request->discount_percent;

        // SR-07: Max discount is 15%. Document is NOT blocked for >10%.
        // SR-08 compliance: a Pending approval record is auto-created for Finance visibility.
        if ($discount > 15) {
            return back()->withInput()->with('error', 'Diskon maksimum adalah 15%.');
        }

        [$subtotal, $pbjtAmount, $grossAmount, $parsedLines] = $this->calcTotals($request->lines, $discount);

        $seq = Quotation::max('id') + 1;
        $qId = 'Q/' . now()->format('Y') . '/' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($request, $qId, $discount, $subtotal, $pbjtAmount, $grossAmount, $parsedLines) {
            $q = Quotation::create([
                'quotation_id'     => $qId,
                'customer_id'      => $request->customer_id,
                'crm_lead_id'      => $request->crm_lead_id ?: null,
                'salesperson_id'   => $request->salesperson_id ?: null,
                'quotation_date'   => $request->quotation_date,
                'expiration_date'  => $request->expiration_date ?: null,
                'discount_percent' => $discount,
                'approval_id'      => null,
                'subtotal'         => $subtotal,
                'pbjt_amount'      => $pbjtAmount,
                'gross_amount'     => $grossAmount,
                'status'           => 'Draft',
                'notes'            => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'is_locked'        => false,
            ]);

            foreach ($parsedLines as $line) {
                QuotationLine::create(array_merge($line, ['quotation_id' => $qId]));
            }

            // SR-08 transparency: auto-create Pending record if discount > 10%
            // Finance can Approve or Reject via Discount Approval page.
            // The quotation itself proceeds regardless (SR-07).
            if ($discount > 10) {
                $daSeq = DiscountApproval::max('id') + 1;
                DiscountApproval::create([
                    'approval_code'     => 'DA/' . now()->format('Y') . '/' . str_pad($daSeq, 4, '0', STR_PAD_LEFT),
                    'document_type'     => 'Quotation',
                    'document_id'       => $qId,
                    'customer_id'       => $request->customer_id,
                    'discount_percent'  => $discount,
                    'document_amount'   => $grossAmount,
                    'requested_by'      => Auth::user()->name,
                    'requested_by_role' => Auth::user()->role,
                    'status'            => 'Pending',
                    'notes'             => "Diskon {$discount}% — perlu review Finance (SR-08).",
                ]);
            }

            AuditLog::log('CREATE', 'Quotation', $qId, null, null,
                ['customer_id' => $request->customer_id, 'gross_amount' => $grossAmount, 'discount_percent' => $discount],
                "Quotation {$qId} dibuat (diskon {$discount}%).", Auth::user()->name, Auth::user()->role);
        });

        $warnMsg = $discount > 10
            ? "Quotation {$qId} berhasil dibuat. ⚠️ Diskon {$discount}% — permintaan persetujuan Finance sudah dibuat."
            : "Quotation {$qId} berhasil dibuat.";

        return redirect()->route('sales.quotations.show', $qId)->with('success', $warnMsg);
    }

    public function show(string $id)
    {
        $quotation = Quotation::with(['customer', 'lines.product', 'lead', 'salesperson', 'salesOrder'])
            ->where(function ($q) use ($id) {
                $q->where('quotation_id', $id)->orWhere('id', $id);
            })->firstOrFail();

        $approvalRecord = DiscountApproval::where('document_type', 'Quotation')
            ->where(function ($q) use ($id, $quotation) {
                $q->where('document_id', $id)->orWhere('document_id', $quotation->quotation_id);
            })->first();

        return view('sales.quotations.show', compact('quotation', 'approvalRecord'));
    }

    public function edit(string $id)
    {
        $quotation  = Quotation::with('lines')->where(function ($q) use ($id) {
            $q->where('quotation_id', $id)->orWhere('id', $id);
        })->firstOrFail();

        if ($quotation->is_locked) {
            return redirect()->route('sales.quotations.show', $quotation->quotation_id)
                ->with('error', 'Quotation yang sudah dikonfirmasi tidak dapat diedit.');
        }

        $customers  = Contact::where('contact_type', 'Customer')->orderBy('name')->get();
        $products   = Product::orderBy('variant_sku')->get();
        $salesUsers = User::whereIn('role', ['owner', 'finance', 'server'])->orderBy('name')->get();

        return view('sales.quotations.edit', compact('quotation', 'customers', 'products', 'salesUsers'));
    }

    public function update(Request $request, string $id)
    {
        $quotation = Quotation::with('lines')->where(function ($q) use ($id) {
            $q->where('quotation_id', $id)->orWhere('id', $id);
        })->firstOrFail();

        if ($quotation->is_locked) {
            return back()->with('error', 'Quotation yang sudah dikonfirmasi tidak dapat diedit.');
        }

        $request->validate([
            'customer_id'      => 'required|string|exists:contacts,contact_id',
            'quotation_date'   => 'required|date',
            'expiration_date'  => 'nullable|date',
            'discount_percent' => 'required|numeric|min:0|max:15',
            'notes'            => 'nullable|string',
            'lines'            => 'required|array|min:1',
            'lines.*.variant_sku' => 'required|string|exists:products,variant_sku',
            'lines.*.quantity'    => 'required|numeric|min:1',
            'lines.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $discount = (float) $request->discount_percent;
        [$subtotal, $pbjtAmount, $grossAmount, $parsedLines] = $this->calcTotals($request->lines, $discount);

        DB::transaction(function () use ($request, $quotation, $discount, $subtotal, $pbjtAmount, $grossAmount, $parsedLines) {
            $before = $quotation->toArray();
            $quotation->update([
                'customer_id'      => $request->customer_id,
                'quotation_date'   => $request->quotation_date,
                'expiration_date'  => $request->expiration_date ?: null,
                'discount_percent' => $discount,
                'subtotal'         => $subtotal,
                'pbjt_amount'      => $pbjtAmount,
                'gross_amount'     => $grossAmount,
                'notes'            => $request->notes,
            ]);

            QuotationLine::where('quotation_id', $quotation->quotation_id)->delete();
            foreach ($parsedLines as $line) {
                QuotationLine::create(array_merge($line, ['quotation_id' => $quotation->quotation_id]));
            }

            if ($discount > 10) {
                $existingApproval = DiscountApproval::where('document_type', 'Quotation')
                    ->where('document_id', $quotation->quotation_id)
                    ->where('status', 'Pending')
                    ->first();

                if ($existingApproval) {
                    $existingApproval->update([
                        'customer_id'      => $request->customer_id,
                        'discount_percent' => $discount,
                        'document_amount'  => $grossAmount,
                        'notes'            => "Diskon {$discount}% — diperbarui pada {$quotation->quotation_id} (SR-08).",
                    ]);
                } elseif (!DiscountApproval::where('document_type', 'Quotation')->where('document_id', $quotation->quotation_id)->where('status', 'Approved')->exists()) {
                    $daSeq = DiscountApproval::max('id') + 1;
                    DiscountApproval::create([
                        'approval_code'     => 'DA/' . now()->format('Y') . '/' . str_pad($daSeq, 4, '0', STR_PAD_LEFT),
                        'document_type'     => 'Quotation',
                        'document_id'       => $quotation->quotation_id,
                        'customer_id'       => $request->customer_id,
                        'discount_percent'  => $discount,
                        'document_amount'   => $grossAmount,
                        'requested_by'      => Auth::user()->name,
                        'requested_by_role' => Auth::user()->role,
                        'status'            => 'Pending',
                        'notes'             => "Diskon {$discount}% — perlu review Finance (SR-08).",
                    ]);
                }
            }

            AuditLog::log('UPDATE', 'Quotation', $quotation->quotation_id, null, $before,
                $quotation->fresh()->toArray(), "Quotation diperbarui.",
                Auth::user()->name, Auth::user()->role);
        });

        return redirect()->route('sales.quotations.show', $quotation->quotation_id)->with('success', 'Quotation diperbarui.');
    }

    public function send(Request $request, string $id)
    {
        $q = Quotation::where(function ($query) use ($id) {
            $query->where('quotation_id', $id)->orWhere('id', $id);
        })->firstOrFail();
        if ($q->status !== 'Draft') {
            return back()->with('error', 'Hanya quotation Draft yang dapat dikirim.');
        }
        $q->update(['status' => 'Sent']);
        AuditLog::log('SEND', 'Quotation', $q->quotation_id, null, ['status' => 'Draft'], ['status' => 'Sent'],
            "Quotation {$q->quotation_id} dikirim ke pelanggan.", Auth::user()->name, Auth::user()->role);
        return redirect()->route('sales.quotations.show', $q->quotation_id)->with('success', 'Quotation dikirim.');
    }

    public function confirm(Request $request, string $id)
    {
        $q = Quotation::with(['customer', 'lines'])->where(function ($query) use ($id) {
            $query->where('quotation_id', $id)->orWhere('id', $id);
        })->firstOrFail();

        if (!in_array($q->status, ['Draft', 'Sent'])) {
            return back()->with('error', 'Hanya quotation Draft/Sent yang dapat dikonfirmasi.');
        }

        // SR-15: Credit check saat konfirmasi (sama seperti saat convert to SO)
        $customer = $q->customer;
        if ($customer && $customer->credit_limit > 0) {
            $check = $customer->checkCreditLimit((float) $q->gross_amount);

            if (!$check['passed']) {
                AuditLog::log('CREDIT_BLOCK', 'Quotation', $q->quotation_id, null,
                    ['current_exposure' => $check['current_exposure'], 'credit_limit' => $check['credit_limit']],
                    ['candidate' => $check['candidate_amount'], 'exceeded_by' => $check['exceeded_by']],
                    "Konfirmasi Quotation {$q->quotation_id} ditolak: batas kredit terlampaui Rp " . number_format($check['exceeded_by'], 0, ',', '.') . ".",
                    Auth::user()?->name ?? 'System', Auth::user()?->role ?? 'finance');

                return back()->with('error',
                    "Konfirmasi ditolak (SR-15): batas kredit terlampaui Rp " .
                    number_format($check['exceeded_by'], 0, ',', '.') .
                    " (Eksposur: Rp " . number_format($check['current_exposure'], 0, ',', '.') .
                    " / Limit: Rp " . number_format($check['credit_limit'], 0, ',', '.') . ")."
                );
            }
        }

        $q->update(['status' => 'Accepted', 'is_locked' => true]);
        AuditLog::log('CONFIRM', 'Quotation', $q->quotation_id, null, ['status' => $q->getOriginal('status')],
            ['status' => 'Accepted', 'is_locked' => true],
            "Quotation {$q->quotation_id} dikonfirmasi.", Auth::user()->name, Auth::user()->role);
        return redirect()->route('sales.quotations.show', $q->quotation_id)->with('success', 'Quotation dikonfirmasi.');
    }

    public function cancel(Request $request, string $id)
    {
        $q = Quotation::where(function ($query) use ($id) {
            $query->where('quotation_id', $id)->orWhere('id', $id);
        })->firstOrFail();
        if (in_array($q->status, ['Converted', 'Rejected'])) {
            return back()->with('error', 'Quotation ini tidak dapat dibatalkan.');
        }
        $q->update(['status' => 'Rejected']);
        AuditLog::log('CANCEL', 'Quotation', $q->quotation_id, null, ['status' => $q->getOriginal('status')],
            ['status' => 'Rejected'], "Quotation {$q->quotation_id} dibatalkan.", Auth::user()->name, Auth::user()->role);
        return redirect()->route('sales.quotations.show', $q->quotation_id)->with('success', 'Quotation dibatalkan.');
    }

    public function print(string $id)
    {
        $quotation = Quotation::with(['customer', 'lines.product', 'salesperson'])
            ->where(function ($query) use ($id) {
                $query->where('quotation_id', $id)->orWhere('id', $id);
            })->firstOrFail();
        return view('sales.quotations.print', compact('quotation'));
    }

    public function convertToOrder(Request $request, string $id)
    {
        $q = Quotation::with(['customer', 'lines'])->where(function ($query) use ($id) {
            $query->where('quotation_id', $id)->orWhere('id', $id);
        })->firstOrFail();

        if ($q->status !== 'Accepted') {
            return back()->with('error', 'Hanya quotation Accepted yang dapat dikonversi ke Sales Order.');
        }
        if ($q->salesOrder()->exists()) {
            return back()->with('error', 'Sales Order sudah dibuat dari quotation ini.');
        }

        // Credit check
        $customer = $q->customer;
        if ($customer && $customer->credit_limit > 0) {
            $check = $customer->checkCreditLimit((float) $q->gross_amount);

            $cvSeq = \App\Models\CreditValidation::max('id') + 1;
            \App\Models\CreditValidation::create([
                'validation_code'     => 'CV/' . now()->format('Y') . '/' . str_pad($cvSeq, 4, '0', STR_PAD_LEFT),
                'document_type'       => 'Quotation',
                'document_id'         => $q->quotation_id,
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

            if (!$check['passed']) {
                return back()->with('error',
                    "Konfirmasi ditolak: batas kredit terlampaui Rp " .
                    number_format($check['exceeded_by'], 0, ',', '.') .
                    " (Eksposur: Rp " . number_format($check['current_exposure'], 0, ',', '.') .
                    " / Limit: Rp " . number_format($check['credit_limit'], 0, ',', '.') . ")."
                );
            }
        }

        DB::transaction(function () use ($q) {
            $seq     = SalesOrder::max('id') + 1;
            $orderId = 'S' . str_pad($seq, 5, '0', STR_PAD_LEFT);

            SalesOrder::create([
                'order_id'          => $orderId,
                'customer_id'       => $q->customer_id,
                'quotation_id'      => $q->quotation_id,
                'crm_lead_id'       => $q->crm_lead_id,
                'salesperson_id'    => $q->salesperson_id,
                'status'            => 'Confirmed',
                'payment_terms'     => $q->customer?->payment_terms ?? '',
                'order_date'        => now()->toDateString(),
                'confirmation_date' => now()->toDateString(),
                'discount_percent'  => $q->discount_percent,
                'approval_id'       => null,
                'subtotal'          => $q->subtotal,
                'pbjt_amount'       => $q->pbjt_amount,
                'gross_amount'      => $q->gross_amount,
                'is_locked'         => true,
                'delivery_status'   => 'Pending',
                'invoice_status'    => 'To Invoice',
                'notes'             => $q->notes,
            ]);

            foreach ($q->lines as $ql) {
                SalesOrderLine::create([
                    'order_id'         => $orderId,
                    'variant_sku'      => $ql->variant_sku,
                    'ordered_qty'      => $ql->quantity,
                    'delivered_qty'    => 0,
                    'invoiced_qty'     => 0,
                    'unit_price'       => $ql->unit_price,
                    'discount_percent' => $ql->discount_percent,
                    'subtotal'         => $ql->subtotal,
                    'pbjt_amount'      => $ql->pbjt_amount,
                    'total_amount'     => $ql->total_amount,
                ]);

                // Reserve inventory for confirmed items (Integration with Inventory / T-11)
                $stock = \App\Models\StockLevel::where('variant_sku', $ql->variant_sku)->first();
                if ($stock) {
                    $stock->increment('reserved', (float) $ql->quantity);
                }
            }

            // SR-08 transparency: if discount > 10%, ensure there's a Pending approval record
            // so Finance can review. Only create if none exists for this quotation.
            $disc = (float) $q->discount_percent;
            if ($disc > 10) {
                $existingApproval = DiscountApproval::where('document_type', 'Quotation')
                    ->where('document_id', $q->quotation_id)->first();
                if (!$existingApproval) {
                    $daSeq = DiscountApproval::max('id') + 1;
                    DiscountApproval::create([
                        'approval_code'     => 'DA/' . now()->format('Y') . '/' . str_pad($daSeq, 4, '0', STR_PAD_LEFT),
                        'document_type'     => 'SalesOrder',
                        'document_id'       => $orderId,
                        'customer_id'       => $q->customer_id,
                        'discount_percent'  => $disc,
                        'document_amount'   => $q->gross_amount,
                        'requested_by'      => Auth::user()->name,
                        'requested_by_role' => Auth::user()->role,
                        'status'            => 'Pending',
                        'notes'             => "Diskon {$disc}% dari Quotation {$q->quotation_id}.",
                    ]);
                }
            }

            $q->update(['status' => 'Converted']);

            AuditLog::log('CONVERT_TO_SO', 'Quotation', $q->quotation_id, $orderId,
                ['status' => 'Accepted'], ['status' => 'Converted', 'sales_order_id' => $orderId],
                "Quotation {$q->quotation_id} dikonversi ke SO {$orderId}.",
                Auth::user()->name, Auth::user()->role);
        });

        $so = SalesOrder::where('quotation_id', $q->quotation_id)->first();
        return redirect()->route('sales.orders.show', $so->order_id)
            ->with('success', "Sales Order {$so->order_id} berhasil dibuat dari Quotation.");
    }

    private function calcTotals(array $rawLines, float $disc): array
    {
        $subtotal = $pbjt = 0;
        $lines    = [];

        foreach ($rawLines as $l) {
            $qty      = (float) $l['quantity'];
            $price    = (float) $l['unit_price'];
            $lineDisc = (float) ($l['discount_percent'] ?? $disc);
            $net      = round($qty * $price * (1 - $lineDisc / 100), 2);
            $tax      = round($net * self::PBJT_RATE);

            $subtotal += $net;
            $pbjt     += $tax;

            $lines[] = [
                'variant_sku'      => $l['variant_sku'],
                'quantity'         => $qty,
                'unit_price'       => $price,
                'discount_percent' => $lineDisc,
                'subtotal'         => $net,
                'pbjt_rate'        => self::PBJT_RATE,
                'pbjt_amount'      => $tax,
                'total_amount'     => $net + $tax,
            ];
        }

        return [$subtotal, $pbjt, $subtotal + $pbjt, $lines];
    }
}
