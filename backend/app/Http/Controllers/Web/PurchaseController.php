<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Receipt;
use App\Models\ReceiptLine;
use App\Models\StockLevel;
use App\Models\StockMove;
use App\Models\VendorPricelist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::with('vendor');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function($sub) use ($q) {
                $sub->where('po_id', 'like', "%{$q}%")
                    ->orWhereHas('vendor', fn($v) => $v->where('name', 'like', "%{$q}%"));
            });
        }

        $orders   = $query->orderByDesc('order_date')->orderByDesc('id')->paginate(15)->withQueryString();
        $statuses = ['RFQ', 'Confirmed', 'Done', 'Cancelled'];
        return view('purchase.index', compact('orders', 'statuses'));
    }

    public function create()
    {
        $vendors  = Contact::where('contact_type', 'Vendor')->orderBy('name')->get();
        $products = Product::orderBy('variant_sku')->get();
        return view('purchase.create', compact('vendors', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'vendor_id'    => 'required|string|exists:contacts,contact_id',
            'order_date'   => 'required|date',
            'payment_terms' => 'required|string',
            'notes'        => 'nullable|string',
            'lines'        => 'required|array|min:1',
            'lines.*.variant_sku'  => 'required|string|exists:products,variant_sku',
            'lines.*.qty'          => 'required|numeric|min:1',
            'lines.*.unit_price'   => 'nullable|numeric|min:0',
        ]);

        $allowedSkus = VendorPricelist::where('vendor_id', $request->vendor_id)
            ->pluck('variant_sku')
            ->all();

        foreach ($request->lines as $line) {
            if (!in_array($line['variant_sku'], $allowedSkus, true)) {
                return back()->withInput()->with('error', "Produk {$line['variant_sku']} tidak terdaftar pada vendor yang dipilih.");
            }
        }

        $total = 0;
        $lines = [];
        foreach ($request->lines as $line) {
            $lockedPrice = $this->resolveLockedPurchasePrice($request->vendor_id, $line['variant_sku']);
            if (isset($line['unit_price']) && abs((float) $line['unit_price'] - $lockedPrice) > 0.01) {
                AuditLog::log(
                    'ATTEMPT_PRICE_EDIT',
                    'PurchaseOrder',
                    'NEW_PO',
                    null,
                    ['submitted_price' => (float) $line['unit_price']],
                    ['enforced_price' => $lockedPrice, 'variant_sku' => $line['variant_sku']],
                    "Percobaan ubah harga PO untuk {$line['variant_sku']} ditolak; harga dikunci ke Rp " . number_format($lockedPrice, 0, ',', '.'),
                    Auth::user()->name,
                    Auth::user()->role
                );
            }
            $lineTotal = round($line['qty'] * $lockedPrice, 2);
            $total    += $lineTotal;
            $lines[]   = [
                'variant_sku'        => $line['variant_sku'],
                'ordered_qty'        => (float) $line['qty'],
                'received_qty'       => 0,
                'accepted_qty'       => 0,
                'billed_qty'         => 0,
                'unit_purchase_price'=> $lockedPrice,
                'subtotal'           => $lineTotal,
            ];
        }

        if ($termError = $this->validatePaymentTermForTotal($total, $request->payment_terms)) {
            return back()->withInput()->with('error', $termError);
        }

        $seq  = PurchaseOrder::max('id') + 1;
        $poId = 'PO' . str_pad($seq, 5, '0', STR_PAD_LEFT);

        $po = PurchaseOrder::create([
            'po_id'          => $poId,
            'vendor_id'      => $request->vendor_id,
            'status'         => 'RFQ',
            'payment_terms'  => $request->payment_terms,
            'order_date'     => $request->order_date,
            'total_amount'   => $total,
            'is_locked'      => false,
            'receipt_status' => 'Nothing to Receive',
            'billing_status' => 'Nothing to Bill',
            'notes'          => $request->notes,
        ]);

        foreach ($lines as $line) {
            PurchaseOrderLine::create(array_merge($line, ['po_id' => $poId]));
        }

        AuditLog::log('CREATE', 'PurchaseOrder', $poId, null, null,
            ['vendor_id' => $request->vendor_id, 'total' => $total],
            "RFQ {$poId} dibuat.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('purchase.show', $poId)->with('success', "RFQ {$poId} berhasil dibuat.");
    }

    public function show(string $id)
    {
        $po       = PurchaseOrder::with(['vendor', 'lines.product', 'receipts.lines.product', 'bills'])
            ->where(fn($q) => $q->where('po_id', $id)->orWhere('id', $id))
            ->firstOrFail();
        return view('purchase.show', compact('po'));
    }

    public function edit(string $id)
    {
        $po = PurchaseOrder::with('lines')
            ->where(fn($q) => $q->where('po_id', $id)->orWhere('id', $id))
            ->firstOrFail();
        if ($po->is_locked) {
            return redirect()->route('purchase.show', $po->po_id)->with('error', 'PO yang sudah dikonfirmasi tidak dapat diedit.');
        }
        $vendors  = Contact::where('contact_type', 'Vendor')->orderBy('name')->get();
        $products = Product::orderBy('variant_sku')->get();
        return view('purchase.edit', compact('po', 'vendors', 'products'));
    }

    public function update(Request $request, string $id)
    {
        $po = PurchaseOrder::where(fn($q) => $q->where('po_id', $id)->orWhere('id', $id))->firstOrFail();
        if ($po->is_locked) {
            return back()->with('error', 'PO yang sudah dikonfirmasi tidak dapat diedit.');
        }

        $request->validate([
            'vendor_id'    => 'required|string|exists:contacts,contact_id',
            'order_date'   => 'required|date',
            'payment_terms' => 'required|string',
            'notes'        => 'nullable|string',
            'lines'        => 'required|array|min:1',
            'lines.*.variant_sku'  => 'required|string|exists:products,variant_sku',
            'lines.*.qty'          => 'required|numeric|min:1',
            'lines.*.unit_price'   => 'nullable|numeric|min:0',
        ]);

        $allowedSkus = VendorPricelist::where('vendor_id', $request->vendor_id)
            ->pluck('variant_sku')
            ->all();

        foreach ($request->lines as $line) {
            if (!in_array($line['variant_sku'], $allowedSkus, true)) {
                return back()->withInput()->with('error', "Produk {$line['variant_sku']} tidak terdaftar pada vendor yang dipilih.");
            }
        }

        $allowedSkus = VendorPricelist::where('vendor_id', $request->vendor_id)
            ->pluck('variant_sku')
            ->all();

        foreach ($request->lines as $line) {
            if (!in_array($line['variant_sku'], $allowedSkus, true)) {
                return back()->withInput()->with('error', "Produk {$line['variant_sku']} tidak terdaftar pada vendor yang dipilih.");
            }
        }

        $total = 0;
        $lines = [];
        foreach ($request->lines as $line) {
            $lockedPrice = $this->resolveLockedPurchasePrice($request->vendor_id, $line['variant_sku']);
            if (isset($line['unit_price']) && abs((float) $line['unit_price'] - $lockedPrice) > 0.01) {
                AuditLog::log(
                    'ATTEMPT_PRICE_EDIT',
                    'PurchaseOrder',
                    $po->po_id,
                    null,
                    ['submitted_price' => (float) $line['unit_price']],
                    ['enforced_price' => $lockedPrice, 'variant_sku' => $line['variant_sku']],
                    "Percobaan ubah harga PO {$po->po_id} untuk {$line['variant_sku']} ditolak; harga dikunci ke Rp " . number_format($lockedPrice, 0, ',', '.'),
                    Auth::user()->name,
                    Auth::user()->role
                );
            }
            $lineTotal = round($line['qty'] * $lockedPrice, 2);
            $total    += $lineTotal;
            $lines[]   = ['variant_sku' => $line['variant_sku'], 'ordered_qty' => (float)$line['qty'], 'received_qty' => 0, 'accepted_qty' => 0, 'billed_qty' => 0, 'unit_purchase_price' => $lockedPrice, 'subtotal' => $lineTotal];
        }

        if ($termError = $this->validatePaymentTermForTotal($total, $request->payment_terms)) {
            return back()->withInput()->with('error', $termError);
        }

        DB::transaction(function () use ($request, $po, $total, $lines) {
            $before = $po->toArray();
            $po->update(['vendor_id' => $request->vendor_id, 'order_date' => $request->order_date, 'payment_terms' => $request->payment_terms, 'total_amount' => $total, 'notes' => $request->notes]);
            PurchaseOrderLine::where('po_id', $po->po_id)->delete();
            foreach ($lines as $line) {
                PurchaseOrderLine::create(array_merge($line, ['po_id' => $po->po_id]));
            }
            AuditLog::log('UPDATE', 'PurchaseOrder', $po->po_id, null, $before, $po->fresh()->toArray(), "PO {$po->po_id} diperbarui.", Auth::user()->name, Auth::user()->role);
        });

        return redirect()->route('purchase.show', $id)->with('success', "PO diperbarui.");
    }

    /**
     * Ambil harga beli permanen dari VendorPricelist (prioritas) atau Product.standard_cost.
     */
    private function resolveLockedPurchasePrice(string $vendorId, string $variantSku): float
    {
        $pricelist = VendorPricelist::where('vendor_id', $vendorId)
            ->where('variant_sku', $variantSku)
            ->first();

        if ($pricelist) {
            return (float) $pricelist->unit_purchase_price;
        }

        return (float) (Product::where('variant_sku', $variantSku)->value('standard_cost') ?? 0);
    }

    /**
     * Aturan manajemen keuangan untuk termin pembayaran PO berdasarkan total nominal:
     * - < Rp 3.000.000        : wajib Tunai (Cash)
     * - Rp 3.000.000 - 10 jt  : 7 hari atau 14 hari
     * - > Rp 10.000.000       : 14 hari atau 30 hari
     */
    private function validatePaymentTermForTotal(float $total, string $paymentTerms): ?string
    {
        if ($total < 3000000) {
            return $paymentTerms === 'Tunai'
                ? null
                : 'Pembelian di bawah Rp 3.000.000 wajib menggunakan termin Tunai (Cash).';
        }

        if ($total <= 10000000) {
            return in_array($paymentTerms, ['7 hari', '14 hari'], true)
                ? null
                : 'Pembelian Rp 3.000.000 – Rp 10.000.000 wajib menggunakan termin 7 hari atau 14 hari.';
        }

        return in_array($paymentTerms, ['14 hari', '30 hari'], true)
            ? null
            : 'Pembelian di atas Rp 10.000.000 wajib menggunakan termin 14 hari atau 30 hari.';
    }

    public function confirm(Request $request, string $id)
    {
        $po = PurchaseOrder::where(fn($q) => $q->where('po_id', $id)->orWhere('id', $id))->firstOrFail();
        if ($po->status !== 'RFQ') {
            return back()->with('error', 'Hanya RFQ yang dapat dikonfirmasi.');
        }

        // SR-18: Lock PO
        $po->update(['status' => 'Confirmed', 'is_locked' => true, 'receipt_status' => 'Pending']);

        AuditLog::log('CONFIRM', 'PurchaseOrder', $po->po_id, null, ['status' => 'RFQ'],
            ['status' => 'Confirmed', 'is_locked' => true],
            "PO {$po->po_id} dikonfirmasi dan dikunci.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('purchase.show', $id)->with('success', "PO dikonfirmasi dan dikunci.");
    }

    public function cancel(Request $request, string $id)
    {
        $po = PurchaseOrder::where(fn($q) => $q->where('po_id', $id)->orWhere('id', $id))->firstOrFail();
        if (!in_array($po->status, ['RFQ', 'Confirmed'])) {
            return back()->with('error', 'PO ini tidak dapat dibatalkan.');
        }

        $po->update(['status' => 'Cancelled', 'is_locked' => false]);

        AuditLog::log('CANCEL', 'PurchaseOrder', $po->po_id, null, ['status' => $po->getOriginal('status')],
            ['status' => 'Cancelled'], "PO {$po->po_id} dibatalkan.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('purchase.show', $id)->with('success', "PO dibatalkan.");
    }

    // ─── Receipt ────────────────────────────────────────────────────────────

    public function createReceipt(string $id)
    {
        $po = PurchaseOrder::with('lines.product')->where(fn($q) => $q->where('po_id', $id)->orWhere('id', $id))->firstOrFail();
        if ($po->status !== 'Confirmed') {
            return redirect()->route('purchase.show', $po->po_id)->with('error', 'Hanya PO berstatus Confirmed yang dapat menerima barang.');
        }
        return view('purchase.receipt_create', compact('po'));
    }

    public function storeReceipt(Request $request, string $id)
    {
        $po = PurchaseOrder::with('lines')->where(fn($q) => $q->where('po_id', $id)->orWhere('id', $id))->firstOrFail();

        $request->validate([
            'receipt_date'       => 'required|date',
            'lines'              => 'required|array|min:1',
            'lines.*.variant_sku'   => 'required|string',
            'lines.*.received_qty'  => 'required|numeric|min:0',
            'lines.*.accepted_qty'  => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $po) {
            $seq       = Receipt::max('id') + 1;
            $receiptId = 'WH/IN/' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $receipt = Receipt::create([
                'receipt_id'        => $receiptId,
                'purchase_order_id' => $po->po_id,
                'status'            => 'Done',
                'receipt_date'      => $request->receipt_date,
                'valuation_total'   => 0,
                'is_posted'         => true,
            ]);

            $valuationTotal = 0;

            foreach ($request->lines as $lineData) {
                $poLine = $po->lines->firstWhere('variant_sku', $lineData['variant_sku']);
                if (!$poLine) continue;

                $receivedQty = (float) $lineData['received_qty'];
                $acceptedQty = min((float) $lineData['accepted_qty'], $receivedQty);
                $rejectedQty = $receivedQty - $acceptedQty;

                $product      = Product::where('variant_sku', $lineData['variant_sku'])->first();
                $unitCost     = $poLine->unit_purchase_price;
                $valuation    = round($acceptedQty * $product->standard_cost, 2);
                $valuationTotal += $valuation;

                ReceiptLine::create([
                    'receipt_id'         => $receiptId,
                    'variant_sku'        => $lineData['variant_sku'],
                    'ordered_qty'        => $poLine->ordered_qty,
                    'received_qty'       => $receivedQty,
                    'accepted_qty'       => $acceptedQty,
                    'rejected_qty'       => $rejectedQty,
                    'unit_purchase_price'=> $unitCost,
                    'standard_cost'      => $product->standard_cost,
                    'valuation_amount'   => $valuation,
                ]);

                // SR-06: Only accepted qty increases stock
                if ($acceptedQty > 0) {
                    $loc = str_starts_with($lineData['variant_sku'], 'BV') ? 'BAR-JKT' : 'KITCH-JKT';
                    $sl  = StockLevel::firstOrCreate(
                        ['location_code' => $loc, 'variant_sku' => $lineData['variant_sku']],
                        ['on_hand' => 0, 'reserved' => 0]
                    );
                    $sl->increment('on_hand', $acceptedQty);

                    StockMove::create([
                        'move_code'          => 'IN/' . now()->format('Ymd') . '/' . str_pad(StockMove::count() + 1, 4, '0', STR_PAD_LEFT),
                        'reference_doc_type' => 'Receipt',
                        'reference_doc_id'   => $receiptId,
                        'from_location'      => 'Vendor',
                        'to_location'        => $loc,
                        'variant_sku'        => $lineData['variant_sku'],
                        'quantity'           => $acceptedQty,
                        'unit_cost'          => $product->standard_cost,
                        'total_value'        => $valuation,
                        'status'             => 'Done',
                    ]);
                }

                // Update PO line quantities
                $poLine->increment('received_qty', $receivedQty);
                $poLine->increment('accepted_qty', $acceptedQty);
            }

            $receipt->update(['valuation_total' => $valuationTotal]);

            // GRNI Journal Entry: DR Inventory, CR GRNI
            $jeId = 'JE/' . now()->format('Y') . '/' . str_pad(JournalEntry::max('id') + 1, 4, '0', STR_PAD_LEFT);
            $je = JournalEntry::create([
                'entry_id'       => $jeId,
                'entry_date'     => $request->receipt_date,
                'reference_type' => 'Receipt',
                'reference_id'   => $receiptId,
                'description'    => "Penerimaan barang {$receiptId}",
                'status'         => 'Posted',
                'total_debit'    => $valuationTotal,
                'total_credit'   => $valuationTotal,
                'is_posted'      => true,
            ]);

            JournalItem::create(['entry_id' => $jeId, 'account_code' => '1200', 'account_name' => 'Persediaan', 'description' => "Terima barang {$receiptId}", 'debit' => $valuationTotal, 'credit' => 0]);
            JournalItem::create(['entry_id' => $jeId, 'account_code' => '2030', 'account_name' => 'GRNI: Barang Diterima Belum Ditagih', 'description' => "GRNI {$receiptId}", 'debit' => 0, 'credit' => $valuationTotal]);

            $po->update(['receipt_status' => 'Received', 'billing_status' => 'To Bill']);

            AuditLog::log('VALIDATE', 'Receipt', $receiptId, $po->po_id, null,
                ['valuation_total' => $valuationTotal],
                "Receipt {$receiptId} divalidasi. GRNI diposting.",
                Auth::user()->name, Auth::user()->role);
        });

        return redirect()->route('purchase.show', $id)->with('success', "Penerimaan barang berhasil divalidasi.");
    }

    public function showReceipt(string $rid)
    {
        $receipt = Receipt::with(['purchaseOrder.vendor', 'lines.product'])
            ->where(fn($q) => $q->where('receipt_id', $rid)->orWhere('id', $rid))
            ->firstOrFail();
        return view('purchase.receipt_show', compact('receipt'));
    }

    public function validateReceipt(Request $request, string $rid)
    {
        // Receipt sudah divalidasi saat storeReceipt() — method ini handle re-check
        $receipt = \App\Models\Receipt::where(fn($q) => $q->where('receipt_id', $rid)->orWhere('id', $rid))->firstOrFail();
        return redirect()->route('purchase.receipt.show', $receipt->receipt_id)
            ->with('info', 'Receipt ini sudah diproses dan stok telah diperbarui.');
    }
}
