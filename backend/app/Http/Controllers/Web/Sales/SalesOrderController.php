<?php

namespace App\Http\Controllers\Web\Sales;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\CreditValidation;
use App\Models\DiscountApproval;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    const PBJT_RATE = 0.10;

    public function index(Request $request)
    {
        $query = SalesOrder::with('customer');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('order_id', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$q}%"));
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->where('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('order_date', '<=', $request->date_to);
        }

        $orders   = $query->orderByDesc('order_date')->orderByDesc('id')->paginate(20)->withQueryString();
        $statuses = ['Draft', 'Confirmed', 'Done', 'Cancelled'];

        return view('sales.orders.index', compact('orders', 'statuses'));
    }

    public function create(Request $request)
    {
        $customers = Contact::where('contact_type', 'Customer')->orderBy('name')->get();
        $products  = Product::orderBy('variant_sku')->get();
        $users     = User::whereIn('role', ['owner', 'finance'])->orderBy('name')->get();
        $preCustomer = $request->customer_id;
        return view('sales.orders.create', compact('customers', 'products', 'users', 'preCustomer'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id'      => 'required|string|exists:contacts,contact_id',
            'order_date'       => 'required|date',
            'payment_terms'    => 'required|string',
            'discount_percent' => 'required|numeric|min:0|max:15',
            'notes'            => 'nullable|string',
            'lines'            => 'required|array|min:1',
            'lines.*.variant_sku' => 'required|string|exists:products,variant_sku',
            'lines.*.qty'         => 'required|numeric|min:1',
            'lines.*.price'       => 'required|numeric|min:0',
        ]);

        $discount   = (float) $request->discount_percent;

        // SR-07: Max discount is 15%. Document is NOT blocked for >10%.
        // SR-08 compliance: Pending approval record is auto-created for Finance.
        if ($discount > 15) {
            return back()->withInput()->with('error', 'Diskon maksimum adalah 15%.');
        }

        [$subtotal, $pbjtAmount, $grossAmount, $parsedLines] = $this->calcTotals($request->lines, $discount);

        $seq     = SalesOrder::max('id') + 1;
        $orderId = 'S' . str_pad($seq, 5, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($request, $orderId, $discount, $subtotal, $pbjtAmount, $grossAmount, $parsedLines) {
            SalesOrder::create([
                'order_id'         => $orderId,
                'customer_id'      => $request->customer_id,
                'crm_lead_id'      => $request->crm_lead_id ?: null,
                'quotation_id'     => $request->quotation_id ?: null,
                'salesperson_id'   => $request->salesperson_id ?: null,
                'status'           => 'Draft',
                'payment_terms'    => $request->payment_terms,
                'order_date'       => $request->order_date,
                'discount_percent' => $discount,
                'approval_id'      => null,
                'subtotal'         => $subtotal,
                'pbjt_amount'      => $pbjtAmount,
                'gross_amount'     => $grossAmount,
                'is_locked'        => false,
                'delivery_status'  => 'Pending',
                'invoice_status'   => 'To Invoice',
                'notes'            => $request->notes,
            ]);

            foreach ($parsedLines as $line) {
                SalesOrderLine::create(array_merge($line, ['order_id' => $orderId]));
            }

            // SR-08 transparency: auto-create Pending record if discount > 10%
            if ($discount > 10) {
                $daSeq = \App\Models\DiscountApproval::max('id') + 1;
                \App\Models\DiscountApproval::create([
                    'approval_code'     => 'DA/' . now()->format('Y') . '/' . str_pad($daSeq, 4, '0', STR_PAD_LEFT),
                    'document_type'     => 'SalesOrder',
                    'document_id'       => $orderId,
                    'customer_id'       => $request->customer_id,
                    'discount_percent'  => $discount,
                    'document_amount'   => $grossAmount,
                    'requested_by'      => Auth::user()->name,
                    'requested_by_role' => Auth::user()->role,
                    'status'            => 'Pending',
                    'notes'             => "Diskon {$discount}% — perlu review Finance (SR-08).",
                ]);
            }

            AuditLog::log('CREATE', 'SalesOrder', $orderId, null, null,
                ['customer_id' => $request->customer_id, 'gross_amount' => $grossAmount, 'discount_percent' => $discount],
                "SO {$orderId} dibuat sebagai Draft (diskon {$discount}%).", Auth::user()->name, Auth::user()->role);
        });

        $warnMsg = $discount > 10
            ? "Sales Order {$orderId} berhasil dibuat. ⚠️ Diskon {$discount}% — permintaan persetujuan Finance sudah dibuat."
            : "Sales Order {$orderId} berhasil dibuat.";

        return redirect()->route('sales.orders.show', $orderId)->with('success', $warnMsg);
    }

    public function show(string $id)
    {
        $order = SalesOrder::with([
            'customer', 'lines.product', 'quotation', 'crmLead', 'salesperson',
        ])->where(function ($q) use ($id) {
            $q->where('order_id', $id)->orWhere('id', $id);
        })->firstOrFail();

        $approvalRecord = DiscountApproval::where(function ($q) use ($id, $order) {
                $q->where('document_id', $id)
                  ->orWhere('document_id', $order->order_id)
                  ->when($order->quotation_id, fn($sub) => $sub->orWhere('document_id', $order->quotation_id));
            })
            ->whereIn('document_type', ['SalesOrder', 'Quotation'])
            ->latest()
            ->first();

        $creditValidation = CreditValidation::where(function ($q) use ($id, $order) {
                $q->where(fn($s) => $s->where('document_type', 'SalesOrder')->whereIn('document_id', [$id, $order->order_id]))
                  ->when($order->quotation_id, fn($s) => $s->orWhere(fn($sub) => $sub->where('document_type', 'Quotation')->where('document_id', $order->quotation_id)));
            })
            ->latest()
            ->first();
        $auditLogs        = AuditLog::where('document_type', 'SalesOrder')
            ->where(function ($q) use ($id, $order) {
                $q->where('document_id', $id)->orWhere('document_id', $order->order_id);
            })->orderByDesc('created_at')->get();

        return view('sales.orders.show', compact(
            'order', 'approvalRecord', 'creditValidation', 'auditLogs'
        ));
    }

    public function edit(string $id)
    {
        $order = SalesOrder::with('lines')->where(function ($q) use ($id) {
            $q->where('order_id', $id)->orWhere('id', $id);
        })->firstOrFail();

        if ($order->is_locked) {
            AuditLog::log('ATTEMPT_PRICE_EDIT', 'SalesOrder', $order->order_id, null,
                ['status' => $order->status, 'is_locked' => true, 'gross_amount' => $order->gross_amount], null,
                "Edit ditolak: SO {$order->order_id} sudah terkunci (Confirmed). SR-24.",
                Auth::user()?->name ?? 'System', Auth::user()?->role ?? 'user');

            return redirect()->route('sales.orders.show', $order->order_id)
                ->with('error', 'Sales Order yang sudah dikonfirmasi tidak dapat diedit.');
        }

        $customers = Contact::where('contact_type', 'Customer')->orderBy('name')->get();
        $products  = Product::orderBy('variant_sku')->get();
        $users     = User::whereIn('role', ['owner', 'finance'])->orderBy('name')->get();

        return view('sales.orders.edit', compact('order', 'customers', 'products', 'users'));
    }

    public function update(Request $request, string $id)
    {
        $order = SalesOrder::where(function ($q) use ($id) {
            $q->where('order_id', $id)->orWhere('id', $id);
        })->firstOrFail();

        if ($order->is_locked) {
            AuditLog::log('ATTEMPT_PRICE_EDIT', 'SalesOrder', $id, null,
                ['status' => $order->status, 'is_locked' => true, 'gross_amount' => $order->gross_amount], null,
                "Update ditolak: SO {$id} sudah terkunci (Confirmed). SR-24.",
                Auth::user()?->name ?? 'System', Auth::user()?->role ?? 'user');

            return back()->with('error', 'SO yang sudah dikonfirmasi tidak dapat diedit.');
        }

        $request->validate([
            'customer_id'      => 'required|string|exists:contacts,contact_id',
            'order_date'       => 'required|date',
            'payment_terms'    => 'required|string',
            'discount_percent' => 'required|numeric|min:0|max:15',
            'lines'            => 'required|array|min:1',
            'lines.*.variant_sku' => 'required|string|exists:products,variant_sku',
            'lines.*.qty'         => 'required|numeric|min:1',
            'lines.*.price'       => 'required|numeric|min:0',
        ]);

        $discount = (float) $request->discount_percent;
        [$subtotal, $pbjtAmount, $grossAmount, $parsedLines] = $this->calcTotals($request->lines, $discount);

        DB::transaction(function () use ($request, $order, $discount, $subtotal, $pbjtAmount, $grossAmount, $parsedLines) {
            $before = $order->toArray();
            $order->update([
                'customer_id'      => $request->customer_id,
                'order_date'       => $request->order_date,
                'payment_terms'    => $request->payment_terms,
                'discount_percent' => $discount,
                'subtotal'         => $subtotal,
                'pbjt_amount'      => $pbjtAmount,
                'gross_amount'     => $grossAmount,
                'notes'            => $request->notes,
            ]);

            SalesOrderLine::where('order_id', $order->order_id)->delete();
            foreach ($parsedLines as $line) {
                SalesOrderLine::create(array_merge($line, ['order_id' => $order->order_id]));
            }

            if ($discount > 10) {
                $existingApproval = DiscountApproval::where('document_type', 'SalesOrder')
                    ->where('document_id', $order->order_id)
                    ->where('status', 'Pending')
                    ->first();

                if ($existingApproval) {
                    $existingApproval->update([
                        'customer_id'      => $request->customer_id,
                        'discount_percent' => $discount,
                        'document_amount'  => $grossAmount,
                        'notes'            => "Diskon {$discount}% — diperbarui pada {$order->order_id} (SR-08).",
                    ]);
                } elseif (!DiscountApproval::where('document_type', 'SalesOrder')->where('document_id', $order->order_id)->where('status', 'Approved')->exists()) {
                    $daSeq = DiscountApproval::max('id') + 1;
                    DiscountApproval::create([
                        'approval_code'     => 'DA/' . now()->format('Y') . '/' . str_pad($daSeq, 4, '0', STR_PAD_LEFT),
                        'document_type'     => 'SalesOrder',
                        'document_id'       => $order->order_id,
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

            AuditLog::log('UPDATE', 'SalesOrder', $order->order_id, null, $before,
                $order->fresh()->toArray(), "SO {$order->order_id} diperbarui.",
                Auth::user()->name, Auth::user()->role);
        });

        return redirect()->route('sales.orders.show', $order->order_id)->with('success', 'Sales Order diperbarui.');
    }

    public function confirm(Request $request, string $id)
    {
        $order = SalesOrder::with(['lines', 'customer'])->where(function ($q) use ($id) {
            $q->where('order_id', $id)->orWhere('id', $id);
        })->firstOrFail();

        if ($order->status !== 'Draft') {
            return back()->with('error', 'Hanya SO berstatus Draft yang dapat dikonfirmasi.');
        }

        // SR-15: Credit check
        $customer = $order->customer;
        if ($customer && $customer->credit_limit > 0) {
            $check = $customer->checkCreditLimit((float) $order->gross_amount);

            $confirmedSoAmount = (float) SalesOrder::where('customer_id', $customer->contact_id)
                ->where('status', 'Confirmed')
                ->where('invoice_status', '!=', 'Invoiced')
                ->where('order_id', '!=', $order->order_id) // exclude current SO being confirmed
                ->sum('gross_amount');

            // Record validation with accurate breakdown (SR-15 compliant)
            $cvSeq = CreditValidation::max('id') + 1;
            CreditValidation::create([
                'validation_code'     => 'CV/' . now()->format('Y') . '/' . str_pad($cvSeq, 4, '0', STR_PAD_LEFT),
                'document_type'       => 'SalesOrder',
                'document_id'         => $order->order_id,
                'customer_id'         => $customer->contact_id,
                'credit_limit'        => $check['credit_limit'],
                'opening_ar'          => $check['opening_ar'],
                'unpaid_invoices'     => $check['unpaid_invoices'],
                'confirmed_so_amount' => $confirmedSoAmount,
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

        DB::transaction(function () use ($order) {
            $order->update([
                'status'            => 'Confirmed',
                'is_locked'         => true,
                'confirmation_date' => now()->toDateString(),
            ]);

            // Reserve inventory for confirmed items (Integration with Inventory / T-11)
            foreach ($order->lines as $line) {
                $stock = StockLevel::where('variant_sku', $line->variant_sku)->first();
                if ($stock) {
                    $stock->increment('reserved', (float) $line->ordered_qty);
                }
            }

            AuditLog::log('CONFIRM', 'SalesOrder', $order->order_id, null,
                ['status' => 'Draft'], ['status' => 'Confirmed', 'is_locked' => true],
                "SO {$order->order_id} dikonfirmasi.", Auth::user()?->name ?? 'System', Auth::user()?->role ?? 'user');
        });

        return redirect()->route('sales.orders.show', $id)
            ->with('success', 'Sales Order dikonfirmasi.');
    }

    public function cancel(Request $request, string $id)
    {
        $order = SalesOrder::where(function ($q) use ($id) {
            $q->where('order_id', $id)->orWhere('id', $id);
        })->firstOrFail();

        if (!in_array($order->status, ['Draft', 'Confirmed'])) {
            return back()->with('error', 'SO ini tidak dapat dibatalkan.');
        }

        DB::transaction(function () use ($order) {
            $prevStatus = $order->status;
            // Release any reserved stock if was confirmed
            if ($prevStatus === 'Confirmed') {
                foreach ($order->lines as $line) {
                    $stock = StockLevel::where('variant_sku', $line->variant_sku)->first();
                    if ($stock && $stock->reserved >= $line->ordered_qty) {
                        $stock->decrement('reserved', (float) $line->ordered_qty);
                    }
                }
            }

            $order->update(['status' => 'Cancelled', 'is_locked' => false]);

            AuditLog::log('CANCEL', 'SalesOrder', $order->order_id, null,
                ['status' => $prevStatus], ['status' => 'Cancelled'],
                "SO {$order->order_id} dibatalkan.", Auth::user()?->name ?? 'System', Auth::user()?->role ?? 'user');
        });

        return redirect()->route('sales.orders.show', $id)->with('success', 'Sales Order dibatalkan.');
    }

    private function calcTotals(array $rawLines, float $disc): array
    {
        $subtotal = $pbjt = 0;
        $lines    = [];

        foreach ($rawLines as $l) {
            $qty   = (float) $l['qty'];
            $price = (float) $l['price'];
            $net   = round($qty * $price * (1 - $disc / 100), 2);
            $tax   = round($net * self::PBJT_RATE);

            $subtotal += $net;
            $pbjt     += $tax;

            $lines[] = [
                'variant_sku'      => $l['variant_sku'],
                'ordered_qty'      => $qty,
                'delivered_qty'    => 0,
                'invoiced_qty'     => 0,
                'unit_price'       => $price,
                'discount_percent' => $disc,
                'subtotal'         => $net,
                'pbjt_amount'      => $tax,
                'total_amount'     => $net + $tax,
            ];
        }

        return [$subtotal, $pbjt, $subtotal + $pbjt, $lines];
    }
}
