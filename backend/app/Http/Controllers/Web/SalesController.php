<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderLine;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    const PBJT_RATE = 0.10;

    public function index(Request $request)
    {
        $query = SalesOrder::with('customer');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function($sub) use ($q) {
                $sub->where('order_id', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$q}%"));
            });
        }

        $orders   = $query->orderByDesc('order_date')->orderByDesc('id')->paginate(15)->withQueryString();
        $statuses = ['Draft', 'Confirmed', 'Done', 'Cancelled'];

        return view('sales.index', compact('orders', 'statuses'));
    }

    public function create(Request $request)
    {
        $customers = Contact::where('contact_type', 'Customer')->orderBy('name')->get();
        $products  = Product::orderBy('variant_sku')->get();
        $preCustomer = $request->customer_id;
        $crmLeadId   = $request->crm_lead_id;
        return view('sales.create', compact('customers', 'products', 'preCustomer', 'crmLeadId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id'    => 'required|string|exists:contacts,contact_id',
            'order_date'     => 'required|date',
            'payment_terms'  => 'required|string',
            'discount_percent' => 'required|numeric|min:0|max:15',
            'notes'          => 'nullable|string',
            'lines'          => 'required|array|min:1',
            'lines.*.variant_sku' => 'required|string|exists:products,variant_sku',
            'lines.*.qty'    => 'required|numeric|min:1',
            'lines.*.price'  => 'required|numeric|min:0',
        ]);

        $discount = (float) $request->discount_percent;

        // SR-08: Discount >10% needs manager approval
        if ($discount > 15) {
            return back()->withInput()->with('error', 'Diskon maksimum adalah 15%.');
        }
        if ($discount > 10) {
            $request->validate(['approval_id' => 'required|exists:users,id']);
            $approver = User::find($request->approval_id);
            if (!in_array($approver->role, ['owner', 'finance'])) {
                return back()->withInput()->with('error', 'Approval ID harus merujuk ke Manager/Finance/Owner.');
            }
        }

        // Calculate totals
        [$subtotal, $pbjtAmount, $grossAmount, $lines] = $this->calculateTotals(
            $request->lines, $discount
        );

        // Generate order_id
        $seq = SalesOrder::max('id') + 1;
        $orderId = 'S' . str_pad($seq, 5, '0', STR_PAD_LEFT);

        $order = SalesOrder::create([
            'order_id'         => $orderId,
            'customer_id'      => $request->customer_id,
            'status'           => 'Draft',
            'payment_terms'    => $request->payment_terms,
            'order_date'       => $request->order_date,
            'discount_percent' => $discount,
            'approval_id'      => $discount > 10 ? $request->approval_id : null,
            'subtotal'         => $subtotal,
            'pbjt_amount'      => $pbjtAmount,
            'gross_amount'     => $grossAmount,
            'is_locked'        => false,
            'delivery_status'  => 'Pending',
            'invoice_status'   => 'To Invoice',
            'notes'            => $request->notes,
        ]);

        foreach ($lines as $line) {
            SalesOrderLine::create(array_merge($line, ['order_id' => $orderId]));
        }

        // Link CRM lead if provided
        if ($request->filled('crm_lead_id')) {
            \App\Models\CrmLead::where('lead_id', $request->crm_lead_id)
                ->update(['converted_opportunity_id' => $orderId]);
        }

        AuditLog::log('CREATE', 'SalesOrder', $orderId, null, null,
            ['customer_id' => $order->customer_id, 'gross_amount' => $grossAmount],
            "SO {$orderId} dibuat sebagai Draft.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('sales.show', $orderId)
            ->with('success', "Sales Order {$orderId} berhasil dibuat.");
    }

    public function show(string $id)
    {
        $order     = SalesOrder::with(['customer', 'lines.product', 'deliveryOrders.lines.product'])->where('order_id', $id)->firstOrFail();
        $invoices  = \App\Models\Invoice::where('source_doc_id', $id)->where('source_doc_type', 'Sales Order')->get();
        return view('sales.show', compact('order', 'invoices'));
    }

    public function edit(string $id)
    {
        $order = SalesOrder::with('lines')->where('order_id', $id)->firstOrFail();

        if ($order->is_locked) {
            return redirect()->route('sales.show', $id)->with('error', 'SO yang sudah dikonfirmasi tidak dapat diedit.');
        }

        $customers = Contact::where('contact_type', 'Customer')->orderBy('name')->get();
        $products  = Product::orderBy('variant_sku')->get();
        return view('sales.edit', compact('order', 'customers', 'products'));
    }

    public function update(Request $request, string $id)
    {
        $order = SalesOrder::where('order_id', $id)->firstOrFail();

        if ($order->is_locked) {
            return back()->with('error', 'SO yang sudah dikonfirmasi tidak dapat diedit.');
        }

        $request->validate([
            'customer_id'      => 'required|string|exists:contacts,contact_id',
            'order_date'       => 'required|date',
            'payment_terms'    => 'required|string',
            'discount_percent' => 'required|numeric|min:0|max:15',
            'notes'            => 'nullable|string',
            'lines'            => 'required|array|min:1',
            'lines.*.variant_sku' => 'required|string|exists:products,variant_sku',
            'lines.*.qty'      => 'required|numeric|min:1',
            'lines.*.price'    => 'required|numeric|min:0',
        ]);

        $discount = (float) $request->discount_percent;

        [$subtotal, $pbjtAmount, $grossAmount, $lines] = $this->calculateTotals($request->lines, $discount);

        DB::transaction(function () use ($order, $request, $discount, $subtotal, $pbjtAmount, $grossAmount, $lines) {
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
            foreach ($lines as $line) {
                SalesOrderLine::create(array_merge($line, ['order_id' => $order->order_id]));
            }

            AuditLog::log('UPDATE', 'SalesOrder', $order->order_id, null, $before, $order->fresh()->toArray(),
                "SO {$order->order_id} diperbarui.", Auth::user()->name, Auth::user()->role);
        });

        return redirect()->route('sales.show', $id)->with('success', "Sales Order diperbarui.");
    }

    public function confirm(Request $request, string $id)
    {
        $order = SalesOrder::with(['lines', 'customer'])->where('order_id', $id)->firstOrFail();

        if ($order->status !== 'Draft') {
            return back()->with('error', 'Hanya SO berstatus Draft yang dapat dikonfirmasi.');
        }

        // SR-15: Credit limit check
        $customer = $order->customer;
        if ($customer && $customer->credit_limit > 0) {
            $check = $customer->checkCreditLimit((float) $order->gross_amount);
            if (!$check['passed']) {
                return back()->with('error',
                    "Konfirmasi ditolak: batas kredit terlampaui sebesar Rp " .
                    number_format($check['exceeded_by'], 0, ',', '.') .
                    " (Eksposur saat ini: Rp " . number_format($check['current_exposure'], 0, ',', '.') .
                    ", Limit: Rp " . number_format($check['credit_limit'], 0, ',', '.') . ")."
                );
            }
        }

        DB::transaction(function () use ($order) {
            // SR-10: Reserve stock for each line
            foreach ($order->lines as $line) {
                $stock = StockLevel::where('variant_sku', $line->variant_sku)
                    ->where('location_code', 'KITCH-JKT')
                    ->first();

                // For beverages, try BAR-JKT
                if (!$stock || $stock->available < $line->ordered_qty) {
                    $stock = StockLevel::where('variant_sku', $line->variant_sku)
                        ->where('location_code', 'BAR-JKT')
                        ->first();
                }

                // SR-09: Block if insufficient
                if (!$stock || $stock->available < $line->ordered_qty) {
                    throw new \Exception("Stok tidak cukup untuk {$line->variant_sku}. Tersedia: " . ($stock?->available ?? 0));
                }

                $stock->increment('reserved', $line->ordered_qty);
            }

            $order->update([
                'status'    => 'Confirmed',
                'is_locked' => true,
            ]);

            // Create Delivery Order
            $seq = DeliveryOrder::max('id') + 1;
            $deliveryId = 'WH/OUT/' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $do = DeliveryOrder::create([
                'delivery_id'     => $deliveryId,
                'sales_order_id'  => $order->order_id,
                'status'          => 'Ready',
                'delivery_date'   => now()->toDateString(),
                'cost_of_goods_sold' => 0,
                'is_posted'       => false,
            ]);

            foreach ($order->lines as $line) {
                $product = Product::where('variant_sku', $line->variant_sku)->first();
                DeliveryOrderLine::create([
                    'delivery_id'   => $deliveryId,
                    'variant_sku'   => $line->variant_sku,
                    'ordered_qty'   => $line->ordered_qty,
                    'delivered_qty' => 0,
                    'backorder_qty' => 0,
                    'standard_cost' => $product?->standard_cost ?? 0,
                    'subtotal_cogs' => 0,
                ]);
            }

            AuditLog::log('CONFIRM', 'SalesOrder', $order->order_id, null,
                ['status' => 'Draft'], ['status' => 'Confirmed', 'delivery_id' => $deliveryId],
                "SO {$order->order_id} dikonfirmasi. DO {$deliveryId} dibuat.",
                Auth::user()->name, Auth::user()->role);
        });

        return redirect()->route('sales.show', $id)->with('success', "SO dikonfirmasi dan Delivery Order dibuat.");
    }

    public function cancel(Request $request, string $id)
    {
        $order = SalesOrder::with('lines')->where('order_id', $id)->firstOrFail();

        if (!in_array($order->status, ['Draft', 'Confirmed'])) {
            return back()->with('error', 'SO ini tidak dapat dibatalkan.');
        }

        DB::transaction(function () use ($order) {
            // Release reserved stock if confirmed
            if ($order->status === 'Confirmed') {
                foreach ($order->lines as $line) {
                    StockLevel::where('variant_sku', $line->variant_sku)
                        ->where(function($q) {
                            $q->where('location_code', 'KITCH-JKT')
                              ->orWhere('location_code', 'BAR-JKT');
                        })
                        ->decrement('reserved', $line->ordered_qty);
                }
            }

            $order->update(['status' => 'Cancelled', 'is_locked' => false]);

            AuditLog::log('CANCEL', 'SalesOrder', $order->order_id, null,
                ['status' => $order->getOriginal('status')], ['status' => 'Cancelled'],
                "SO {$order->order_id} dibatalkan.", Auth::user()->name, Auth::user()->role);
        });

        return redirect()->route('sales.show', $id)->with('success', "SO dibatalkan.");
    }

    private function calculateTotals(array $rawLines, float $discountPercent): array
    {
        $subtotal    = 0;
        $pbjtTotal   = 0;
        $parsedLines = [];

        foreach ($rawLines as $line) {
            $qty       = (float) $line['qty'];
            $price     = (float) $line['price'];
            $lineNet   = round($qty * $price * (1 - $discountPercent / 100), 2);
            $linePbjt  = round($lineNet * self::PBJT_RATE);
            $lineTotal = $lineNet + $linePbjt;

            $subtotal  += $lineNet;
            $pbjtTotal += $linePbjt;

            $parsedLines[] = [
                'variant_sku'      => $line['variant_sku'],
                'ordered_qty'      => $qty,
                'delivered_qty'    => 0,
                'invoiced_qty'     => 0,
                'unit_price'       => $price,
                'discount_percent' => $discountPercent,
                'subtotal'         => $lineNet,
                'pbjt_amount'      => $linePbjt,
                'total_amount'     => $lineTotal,
            ];
        }

        $grossAmount = $subtotal + $pbjtTotal;

        return [$subtotal, $pbjtTotal, $grossAmount, $parsedLines];
    }
}
