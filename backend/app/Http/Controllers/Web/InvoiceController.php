<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ReturnOrder;
use App\Models\SalesOrderLine;
use App\Models\StockLevel;
use App\Models\StockMove;
use App\Models\TableOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    const PBJT_RATE = 0.10;

    public function index(Request $request)
    {
        $query = Invoice::with('partner');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function($sub) use ($q) {
                $sub->where('invoice_id', 'like', "%{$q}%")
                    ->orWhereHas('partner', fn($p) => $p->where('name', 'like', "%{$q}%"));
            });
        }

        $invoices = $query->orderByDesc('invoice_date')->orderByDesc('id')->paginate(15)->withQueryString();
        $types    = ['Customer Invoice', 'Vendor Bill'];
        $statuses = ['Draft', 'Posted', 'Paid', 'Cancelled'];

        return view('invoices.index', compact('invoices', 'types', 'statuses'));
    }

    public function show(string $id)
    {
        $invoice  = Invoice::with(['partner', 'lines.product', 'payments', 'delivery', 'receipt'])
            ->where(fn($q) => $q->where('invoice_id', $id)->orWhere('id', $id))
            ->firstOrFail();
        $jePosted = JournalEntry::where('reference_type', 'Customer Invoice')->where(fn($q) => $q->where('reference_id', $id)->orWhere('reference_id', $invoice->invoice_id))->first()
                 ?? JournalEntry::where('reference_type', 'Vendor Bill')->where(fn($q) => $q->where('reference_id', $id)->orWhere('reference_id', $invoice->invoice_id))->first();
        return view('invoices.show', compact('invoice', 'jePosted'));
    }

    public function post(Request $request, string $id)
    {
        $invoice = Invoice::with(['lines', 'partner'])
            ->where(fn($q) => $q->where('invoice_id', $id)->orWhere('id', $id))
            ->firstOrFail();

        if ($invoice->status !== 'Draft') {
            return back()->with('error', 'Hanya Invoice berstatus Draft yang dapat diposting.');
        }

        // SR-20: Idempotency check
        $existingJE = JournalEntry::where('reference_type', $invoice->type)
            ->where('reference_id', $invoice->invoice_id)->exists();
        if ($existingJE) {
            return back()->with('error', 'Journal Entry sudah ada untuk invoice ini (idempotency check).');
        }

        DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => 'Posted', 'is_posted' => true]);

            if ($invoice->type === 'Customer Invoice') {
                $this->postCustomerInvoiceJournal($invoice);

                // SR-02: Update SO invoiced qty for DO-sourced invoices
                if ($invoice->source_doc_type === 'Sales Order' && $invoice->linked_delivery_id) {
                    foreach ($invoice->lines as $line) {
                        SalesOrderLine::where('order_id', $invoice->source_doc_id)
                            ->where('variant_sku', $line->variant_sku)
                            ->increment('invoiced_qty', $line->invoiced_qty);
                    }
                }
            } else {
                $this->postVendorBillJournal($invoice);
            }

            AuditLog::log('POST', 'Invoice', $invoice->invoice_id, null,
                ['status' => 'Draft'], ['status' => 'Posted'],
                "Invoice {$invoice->invoice_id} diposting.", Auth::user()->name, Auth::user()->role);
        });

        return redirect()->route('invoices.show', $id)->with('success', "Invoice diposting. Jurnal akuntansi dibuat.");
    }

    public function cancel(Request $request, string $id)
    {
        $invoice = Invoice::where(fn($q) => $q->where('invoice_id', $id)->orWhere('id', $id))->firstOrFail();

        if ($invoice->status === 'Paid') {
            return back()->with('error', 'Invoice yang sudah dibayar tidak dapat dibatalkan.');
        }

        $invoice->update(['status' => 'Cancelled', 'is_posted' => false]);

        AuditLog::log('CANCEL', 'Invoice', $invoice->invoice_id, null, ['status' => $invoice->getOriginal('status')],
            ['status' => 'Cancelled'], "Invoice {$invoice->invoice_id} dibatalkan.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('invoices.show', $invoice->invoice_id)->with('success', "Invoice dibatalkan.");
    }

    public function payForm(string $id)
    {
        $invoice = Invoice::where(fn($q) => $q->where('invoice_id', $id)->orWhere('id', $id))->firstOrFail();
        if ($invoice->status !== 'Posted') {
            return redirect()->route('invoices.show', $invoice->invoice_id)->with('error', 'Hanya invoice Posted yang dapat dibayar.');
        }
        return view('invoices.pay', compact('invoice'));
    }

    public function pay(Request $request, string $id)
    {
        $invoice = Invoice::where(fn($q) => $q->where('invoice_id', $id)->orWhere('id', $id))->firstOrFail();

        if ($invoice->status !== 'Posted') {
            return back()->with('error', 'Hanya invoice Posted yang dapat dibayar.');
        }

        $request->validate([
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|in:Bank,Kas Restoran',
            'payment_date'   => 'required|date',
            'notes'          => 'nullable|string',
        ]);

        $amount = (float) $request->amount;

        // SR-16: Block overpayment
        if ($amount > (float) $invoice->amount_due) {
            return back()->withInput()->with('error',
                "Jumlah pembayaran (Rp " . number_format($amount, 0, ',', '.') .
                ") melebihi sisa tagihan (Rp " . number_format($invoice->amount_due, 0, ',', '.') . ")."
            );
        }

        DB::transaction(function () use ($invoice, $request, $amount) {
            $seq       = Payment::max('id') + 1;
            $paymentId = 'PAY/' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $newAmountPaid = round((float) $invoice->amount_paid + $amount, 2);
            $newAmountDue  = round((float) $invoice->total_amount - $newAmountPaid, 2);
            $newStatus     = $newAmountDue <= 0 ? 'Paid' : 'Posted';

            Payment::create([
                'payment_id'     => $paymentId,
                'invoice_id'     => $invoice->invoice_id,
                'payment_type'   => $invoice->type === 'Customer Invoice' ? 'Customer Payment' : 'Vendor Payment',
                'payment_method' => $request->payment_method,
                'payment_date'   => $request->payment_date,
                'amount'         => $amount,
                'remaining_due'  => $newAmountDue,
                'is_posted'      => true,
                'notes'          => $request->notes,
            ]);

            $invoice->update([
                'amount_paid' => $newAmountPaid,
                'amount_due'  => $newAmountDue,
                'status'      => $newStatus,
            ]);

            // Journal for payment
            $accountBankCode = $request->payment_method === 'Bank' ? '1010' : '1015';
            $accountBankName = $request->payment_method === 'Bank' ? 'Bank' : 'Kas Restoran';

            $jeId = 'JE/' . now()->format('Y') . '/' . str_pad(JournalEntry::max('id') + 1, 4, '0', STR_PAD_LEFT);

            if ($invoice->type === 'Customer Invoice') {
                // DR Bank, CR AR
                JournalEntry::create([
                    'entry_id' => $jeId, 'entry_date' => $request->payment_date,
                    'reference_type' => 'Payment', 'reference_id' => $paymentId,
                    'description' => "Penerimaan pembayaran {$paymentId} untuk {$invoice->invoice_id}",
                    'status' => 'Posted', 'total_debit' => $amount, 'total_credit' => $amount, 'is_posted' => true,
                ]);
                JournalItem::create(['entry_id' => $jeId, 'account_code' => $accountBankCode, 'account_name' => $accountBankName, 'description' => "Bayar {$invoice->invoice_id}", 'debit' => $amount, 'credit' => 0]);
                JournalItem::create(['entry_id' => $jeId, 'account_code' => '1100', 'account_name' => 'Piutang Usaha', 'description' => "Lunasi AR {$invoice->invoice_id}", 'debit' => 0, 'credit' => $amount]);
            } else {
                // DR AP, CR Bank
                JournalEntry::create([
                    'entry_id' => $jeId, 'entry_date' => $request->payment_date,
                    'reference_type' => 'Payment', 'reference_id' => $paymentId,
                    'description' => "Pembayaran vendor {$paymentId} untuk {$invoice->invoice_id}",
                    'status' => 'Posted', 'total_debit' => $amount, 'total_credit' => $amount, 'is_posted' => true,
                ]);
                JournalItem::create(['entry_id' => $jeId, 'account_code' => '2010', 'account_name' => 'Utang Usaha', 'description' => "Lunasi AP {$invoice->invoice_id}", 'debit' => $amount, 'credit' => 0]);
                JournalItem::create(['entry_id' => $jeId, 'account_code' => $accountBankCode, 'account_name' => $accountBankName, 'description' => "Bayar vendor {$invoice->invoice_id}", 'debit' => 0, 'credit' => $amount]);
            }

            // Mark table order as paid if applicable
            if ($newStatus === 'Paid' && $invoice->source_doc_type === 'Table Order') {
                TableOrder::where('order_id', $invoice->source_doc_id)->update(['status' => 'Paid']);
            }

            AuditLog::log('PAYMENT', 'Payment', $paymentId, $invoice->invoice_id,
                ['amount_due' => $invoice->getOriginal('amount_due')],
                ['amount' => $amount, 'remaining_due' => $newAmountDue],
                "Pembayaran Rp " . number_format($amount, 0, ',', '.') . " untuk {$invoice->invoice_id}.",
                Auth::user()->name, Auth::user()->role);
        });

        return redirect()->route('invoices.show', $id)->with('success', "Pembayaran berhasil dicatat.");
    }

    // ─── Create Invoice from Delivery Order ─────────────────────────────────

    public function createFromDelivery(Request $request, string $did)
    {
        $delivery = DeliveryOrder::with(['salesOrder.customer', 'salesOrder.lines', 'lines'])->where('delivery_id', $did)->firstOrFail();

        if ($delivery->status !== 'Done') {
            return redirect()->route('inventory.delivery.show', $did)->with('error', 'Hanya DO berstatus Done yang dapat diinvoice.');
        }

        $so = $delivery->salesOrder;

        DB::transaction(function () use ($delivery, $so) {
            $seq       = Invoice::max('id') + 1;
            $invoiceId = 'INV/' . now()->format('Y') . '/' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $subtotal = 0;
            $pbjt     = 0;
            $lines    = [];

            foreach ($delivery->lines as $doLine) {
                // SR-02: Only uninvoiced delivered qty
                $soLine      = $so->lines->firstWhere('variant_sku', $doLine->variant_sku);
                $invoiceable = ($doLine->delivered_qty ?? 0) - ($soLine?->invoiced_qty ?? 0);

                if ($invoiceable <= 0) continue;

                $linePrice = $soLine?->unit_price ?? 0;
                $discount  = $soLine?->discount_percent ?? 0;
                $lineNet   = round($invoiceable * $linePrice * (1 - $discount / 100), 2);
                $linePbjt  = round($lineNet * 0.10);

                $subtotal += $lineNet;
                $pbjt     += $linePbjt;
                $lines[]   = [
                    'variant_sku'  => $doLine->variant_sku,
                    'invoiced_qty' => $invoiceable,
                    'unit_price'   => $linePrice,
                    'subtotal'     => $lineNet,
                    'pbjt_rate'    => 0.10,
                    'pbjt_amount'  => $linePbjt,
                    'total_amount' => $lineNet + $linePbjt,
                ];
            }

            if (empty($lines)) {
                throw new \Exception('Tidak ada qty yang bisa diinvoice. Semua sudah diinvoice atau belum dikirim.');
            }

            $total = $subtotal + $pbjt;

            $invoice = Invoice::create([
                'invoice_id'       => $invoiceId,
                'type'             => 'Customer Invoice',
                'partner_id'       => $so->customer_id,
                'source_doc_type'  => 'Sales Order',
                'source_doc_id'    => $so->order_id,
                'linked_delivery_id' => $delivery->delivery_id,
                'invoice_date'     => now()->toDateString(),
                'status'           => 'Draft',
                'subtotal'         => $subtotal,
                'pbjt_amount'      => $pbjt,
                'total_amount'     => $total,
                'amount_paid'      => 0,
                'amount_due'       => $total,
                'is_posted'        => false,
            ]);

            foreach ($lines as $line) {
                InvoiceLine::create(array_merge($line, ['invoice_id' => $invoiceId]));
            }

            AuditLog::log('CREATE_INVOICE', 'Invoice', $invoiceId, $delivery->delivery_id, null,
                ['subtotal' => $subtotal, 'pbjt' => $pbjt, 'total' => $total],
                "Invoice {$invoiceId} dibuat dari DO {$delivery->delivery_id}.",
                Auth::user()->name, Auth::user()->role);
        });

        return redirect()->route('invoices.show', request()->route('invoiceId') ?? Invoice::where('source_doc_id', $so->order_id)->latest()->first()->invoice_id)
            ->with('success', "Invoice berhasil dibuat dari Delivery Order.");
    }

    // ─── Create Vendor Bill from Receipt ────────────────────────────────────

    public function createFromReceipt(Request $request, string $rid)
    {
        $receipt = Receipt::with(['purchaseOrder.vendor', 'purchaseOrder.lines', 'lines'])->where('receipt_id', $rid)->firstOrFail();

        $po = $receipt->purchaseOrder;

        DB::transaction(function () use ($receipt, $po) {
            $seq       = Invoice::max('id') + 1;
            $billId    = 'BILL/' . now()->format('Y') . '/' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $total = 0;
            $lines = [];

            foreach ($receipt->lines as $rLine) {
                // SR-06: Only accepted, uninvoiced qty
                $poLine    = $po->lines->firstWhere('variant_sku', $rLine->variant_sku);
                $billable  = ($rLine->accepted_qty ?? 0) - ($poLine?->billed_qty ?? 0);

                if ($billable <= 0) continue;

                $lineTotal = round($billable * $rLine->unit_purchase_price, 2);
                $total    += $lineTotal;
                $lines[]   = [
                    'variant_sku'  => $rLine->variant_sku,
                    'invoiced_qty' => $billable,
                    'unit_price'   => $rLine->unit_purchase_price,
                    'subtotal'     => $lineTotal,
                    'pbjt_rate'    => 0,
                    'pbjt_amount'  => 0,
                    'total_amount' => $lineTotal,
                ];

                $poLine?->increment('billed_qty', $billable);
            }

            if (empty($lines)) {
                throw new \Exception('Tidak ada qty yang dapat ditagih. Pastikan barang sudah diterima dan diinspeksi.');
            }

            Invoice::create([
                'invoice_id'       => $billId,
                'type'             => 'Vendor Bill',
                'partner_id'       => $po->vendor_id,
                'source_doc_type'  => 'Purchase Order',
                'source_doc_id'    => $po->po_id,
                'linked_receipt_id'=> $receipt->receipt_id,
                'invoice_date'     => now()->toDateString(),
                'status'           => 'Draft',
                'subtotal'         => $total,
                'pbjt_amount'      => 0,
                'total_amount'     => $total,
                'amount_paid'      => 0,
                'amount_due'       => $total,
                'is_posted'        => false,
            ]);

            foreach ($lines as $line) {
                InvoiceLine::create(array_merge($line, ['invoice_id' => $billId]));
            }

            $po->update(['billing_status' => 'Billed']);

            AuditLog::log('CREATE_VENDOR_BILL', 'Invoice', $billId, $receipt->receipt_id, null,
                ['total' => $total],
                "Vendor Bill {$billId} dibuat dari Receipt {$receipt->receipt_id}.",
                Auth::user()->name, Auth::user()->role);
        });

        return redirect()->route('purchase.show', $po->po_id)->with('success', "Vendor Bill berhasil dibuat.");
    }

    // ─── Returns ────────────────────────────────────────────────────────────

    public function returnsIndex()
    {
        $returns = ReturnOrder::with(['invoice', 'customer', 'product'])->orderByDesc('id')->paginate(15);
        return view('invoices.returns_index', compact('returns'));
    }

    public function returnCreate()
    {
        $invoices = Invoice::where('type', 'Customer Invoice')->where('status', 'Posted')
            ->with('partner')->orderByDesc('invoice_date')->get();
        return view('invoices.return_create', compact('invoices'));
    }

    public function returnStore(Request $request)
    {
        $request->validate([
            'original_invoice_id' => 'required|string|exists:invoices,invoice_id',
            'variant_sku'         => 'required|string|exists:products,variant_sku',
            'quantity'            => 'required|numeric|min:1',
            'reason'              => 'required|string',
        ]);

        $invoice = Invoice::with('lines')->where('invoice_id', $request->original_invoice_id)->firstOrFail();
        $invLine = $invoice->lines->firstWhere('variant_sku', $request->variant_sku);

        if (!$invLine) {
            return back()->with('error', 'Produk tidak ditemukan pada invoice tersebut.');
        }

        // Prevent duplicate return
        $existingReturn = ReturnOrder::where('original_invoice_id', $request->original_invoice_id)
            ->where('variant_sku', $request->variant_sku)->where('status', '!=', 'Cancelled')->first();
        if ($existingReturn) {
            return back()->with('error', 'Retur untuk produk ini pada invoice tersebut sudah ada.');
        }

        $qty         = (float) $request->quantity;
        $creditSub   = round($qty * $invLine->unit_price, 2);
        $pbjtRev     = round($creditSub * self::PBJT_RATE);
        $totalCredit = $creditSub + $pbjtRev;

        $product = Product::where('variant_sku', $request->variant_sku)->first();
        $cogsRev = round($qty * ($product?->standard_cost ?? 0), 2);

        $seq      = ReturnOrder::max('id') + 1;
        $returnId = 'RET/' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        $ret = ReturnOrder::create([
            'return_id'           => $returnId,
            'original_invoice_id' => $request->original_invoice_id,
            'customer_id'         => $invoice->partner_id,
            'status'              => 'Draft',
            'variant_sku'         => $request->variant_sku,
            'quantity'            => $qty,
            'credit_subtotal'     => $creditSub,
            'pbjt_reversal'       => $pbjtRev,
            'total_credit_note'   => $totalCredit,
            'cogs_reversal'       => $cogsRev,
            'is_posted'           => false,
            'reason'              => $request->reason,
        ]);

        AuditLog::log('CREATE', 'ReturnOrder', $returnId, $request->original_invoice_id, null,
            ['sku' => $request->variant_sku, 'qty' => $qty, 'total_credit' => $totalCredit],
            "Retur {$returnId} dibuat.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('returns.show', $returnId)->with('success', "Retur {$returnId} dibuat.");
    }

    public function returnShow(string $id)
    {
        $return = ReturnOrder::with(['invoice', 'customer', 'product'])
            ->where(fn($q) => $q->where('return_id', $id)->orWhere('id', $id))
            ->firstOrFail();
        return view('invoices.return_show', compact('return'));
    }

    public function returnValidate(Request $request, string $id)
    {
        $ret = ReturnOrder::with(['invoice', 'product'])
            ->where(fn($q) => $q->where('return_id', $id)->orWhere('id', $id))
            ->firstOrFail();

        if ($ret->status !== 'Draft') {
            return back()->with('error', 'Retur ini sudah diproses.');
        }
        if ($ret->is_posted) {
            return back()->with('error', 'Retur sudah diposting (idempotency check).');
        }

        DB::transaction(function () use ($ret) {
            // Increase stock
            $loc = str_starts_with($ret->variant_sku, 'BV') ? 'BAR-JKT' : 'KITCH-JKT';
            $sl  = StockLevel::firstOrCreate(
                ['location_code' => $loc, 'variant_sku' => $ret->variant_sku],
                ['on_hand' => 0, 'reserved' => 0]
            );
            $sl->increment('on_hand', $ret->quantity);

            StockMove::create([
                'move_code'          => 'RET/' . now()->format('Ymd') . '/' . str_pad(StockMove::count() + 1, 4, '0', STR_PAD_LEFT),
                'reference_doc_type' => 'Return',
                'reference_doc_id'   => $ret->return_id,
                'from_location'      => 'Customer',
                'to_location'        => $loc,
                'variant_sku'        => $ret->variant_sku,
                'quantity'           => $ret->quantity,
                'unit_cost'          => $ret->product?->standard_cost ?? 0,
                'total_value'        => $ret->cogs_reversal,
                'status'             => 'Done',
            ]);

            // Credit Note (invoice with negative effect)
            $seq      = Invoice::max('id') + 1;
            $cnId     = 'CN/' . now()->format('Y') . '/' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            Invoice::create([
                'invoice_id'     => $cnId,
                'type'           => 'Customer Invoice',
                'partner_id'     => $ret->customer_id,
                'source_doc_type'=> 'Return',
                'source_doc_id'  => $ret->return_id,
                'invoice_date'   => now()->toDateString(),
                'status'         => 'Posted',
                'subtotal'       => -$ret->credit_subtotal,
                'pbjt_amount'    => -$ret->pbjt_reversal,
                'total_amount'   => -$ret->total_credit_note,
                'amount_paid'    => 0,
                'amount_due'     => -$ret->total_credit_note,
                'is_posted'      => true,
                'notes'          => "Credit Note untuk Retur {$ret->return_id}",
            ]);

            // Journal: DR Revenue, DR PBJT Payable, CR AR (reversal)
            $jeId = 'JE/' . now()->format('Y') . '/' . str_pad(JournalEntry::max('id') + 1, 4, '0', STR_PAD_LEFT);
            JournalEntry::create([
                'entry_id' => $jeId, 'entry_date' => now()->toDateString(),
                'reference_type' => 'Return', 'reference_id' => $ret->return_id,
                'description' => "Credit Note {$ret->return_id}",
                'status' => 'Posted',
                'total_debit'  => $ret->total_credit_note + $ret->cogs_reversal,
                'total_credit' => $ret->total_credit_note + $ret->cogs_reversal,
                'is_posted' => true,
            ]);

            JournalItem::create(['entry_id' => $jeId, 'account_code' => '4020', 'account_name' => 'Retur Penjualan', 'description' => "Retur {$ret->return_id}", 'debit' => $ret->credit_subtotal, 'credit' => 0]);
            JournalItem::create(['entry_id' => $jeId, 'account_code' => '2020', 'account_name' => 'Utang PBJT', 'description' => "PBJT reversal {$ret->return_id}", 'debit' => $ret->pbjt_reversal, 'credit' => 0]);
            JournalItem::create(['entry_id' => $jeId, 'account_code' => '1100', 'account_name' => 'Piutang Usaha', 'description' => "Kurangi AR {$ret->return_id}", 'debit' => 0, 'credit' => $ret->total_credit_note]);
            JournalItem::create(['entry_id' => $jeId, 'account_code' => '1200', 'account_name' => 'Persediaan', 'description' => "Retur persediaan {$ret->return_id}", 'debit' => $ret->cogs_reversal, 'credit' => 0]);
            JournalItem::create(['entry_id' => $jeId, 'account_code' => '5010', 'account_name' => 'Harga Pokok Penjualan', 'description' => "COGS reversal {$ret->return_id}", 'debit' => 0, 'credit' => $ret->cogs_reversal]);

            $ret->update(['status' => 'Posted', 'is_posted' => true, 'credit_note_id' => $cnId]);

            AuditLog::log('VALIDATE_RETURN', 'ReturnOrder', $ret->return_id, $cnId,
                ['status' => 'Draft'], ['status' => 'Posted'],
                "Retur {$ret->return_id} divalidasi. Credit Note {$cnId} dibuat.",
                Auth::user()->name, Auth::user()->role);
        });

        return redirect()->route('returns.show', $id)->with('success', "Retur divalidasi dan Credit Note diposting.");
    }

    private function postCustomerInvoiceJournal(Invoice $invoice): void
    {
        $jeId = 'JE/' . now()->format('Y') . '/' . str_pad(JournalEntry::max('id') + 1, 4, '0', STR_PAD_LEFT);
        JournalEntry::create([
            'entry_id' => $jeId, 'entry_date' => $invoice->invoice_date ?? now()->toDateString(),
            'reference_type' => 'Customer Invoice', 'reference_id' => $invoice->invoice_id,
            'description' => "Invoice pelanggan {$invoice->invoice_id}",
            'status' => 'Posted', 'total_debit' => $invoice->total_amount, 'total_credit' => $invoice->total_amount, 'is_posted' => true,
        ]);

        JournalItem::create(['entry_id' => $jeId, 'account_code' => '1100', 'account_name' => 'Piutang Usaha', 'description' => "AR {$invoice->invoice_id}", 'debit' => $invoice->total_amount, 'credit' => 0]);
        JournalItem::create(['entry_id' => $jeId, 'account_code' => '4010', 'account_name' => 'Penjualan Makanan/Minuman', 'description' => "Penjualan {$invoice->invoice_id}", 'debit' => 0, 'credit' => $invoice->subtotal]);
        if ($invoice->pbjt_amount > 0) {
            JournalItem::create(['entry_id' => $jeId, 'account_code' => '2020', 'account_name' => 'Utang PBJT', 'description' => "PBJT {$invoice->invoice_id}", 'debit' => 0, 'credit' => $invoice->pbjt_amount]);
        }
    }

    private function postVendorBillJournal(Invoice $invoice): void
    {
        $jeId = 'JE/' . now()->format('Y') . '/' . str_pad(JournalEntry::max('id') + 1, 4, '0', STR_PAD_LEFT);
        JournalEntry::create([
            'entry_id' => $jeId, 'entry_date' => $invoice->invoice_date ?? now()->toDateString(),
            'reference_type' => 'Vendor Bill', 'reference_id' => $invoice->invoice_id,
            'description' => "Tagihan vendor {$invoice->invoice_id}",
            'status' => 'Posted', 'total_debit' => $invoice->total_amount, 'total_credit' => $invoice->total_amount, 'is_posted' => true,
        ]);

        JournalItem::create(['entry_id' => $jeId, 'account_code' => '2030', 'account_name' => 'GRNI: Barang Diterima Belum Ditagih', 'description' => "Lunasi GRNI {$invoice->invoice_id}", 'debit' => $invoice->total_amount, 'credit' => 0]);
        JournalItem::create(['entry_id' => $jeId, 'account_code' => '2010', 'account_name' => 'Utang Usaha', 'description' => "AP {$invoice->invoice_id}", 'debit' => 0, 'credit' => $invoice->total_amount]);
    }
}
