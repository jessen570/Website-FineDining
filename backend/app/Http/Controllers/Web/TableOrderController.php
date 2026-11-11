<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\StockLevel;
use App\Models\StockMove;
use App\Models\TableOrder;
use App\Models\TableOrderLine;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TableOrderController extends Controller
{
    const PBJT_RATE = 0.10;

    public function index()
    {
        $tables  = RestaurantTable::with(['bookings' => fn($q) => $q->whereIn('status', ['Confirmed', 'Checked-In'])])->get();
        $orders  = TableOrder::with(['table', 'server'])->where('status', 'Open')
                    ->orderByDesc('created_at')->get();
        return view('table-orders.index', compact('tables', 'orders'));
    }

    public function create(Request $request)
    {
        // SR-13: Host hanya bisa booking, bukan table order
        if (auth()->user()->role === 'host') {
            return redirect()->route('bookings.index')
                ->with('error', 'Hanya Server, Cashier, atau Owner yang dapat membuka Table Order (SR-13).');
        }

        // Ambil semua meja — termasuk occupied agar bisa dipakai dari booking yang sudah check-in
        $tables    = RestaurantTable::orderBy('table_id')->get();
        $servers   = Employee::where('status', 'Aktif')->orderBy('name')->get();
        $customers = Contact::where('contact_type', 'Customer')->orderBy('name')->get();
        $bookings  = Booking::whereIn('status', ['Checked-In', 'Confirmed'])->orderByDesc('start_time')->get();
        $products  = Product::orderBy('variant_sku')->get();

        // Auto-populate dari booking jika ada booking_id di query string
        $preTable   = $request->table_id;
        $preBooking = $request->booking_id;
        $preCustomer = null;

        if ($preBooking) {
            $booking = Booking::with('customer')->where('booking_id', $preBooking)->first();
            if ($booking) {
                $preTable    = $preTable ?? $booking->table_id;
                $preCustomer = $booking->customer_id;
            }
        }

        return view('table-orders.create', compact(
            'tables', 'servers', 'customers', 'bookings', 'products',
            'preTable', 'preBooking', 'preCustomer'
        ));
    }

    public function store(Request $request)
    {
        // SR-13: Host tidak boleh buat table order
        if (auth()->user()->role === 'host') {
            return redirect()->route('bookings.index')
                ->with('error', 'Hanya Server, Cashier, atau Owner yang dapat membuka Table Order (SR-13).');
        }

        $request->validate([
            'table_id'   => 'required|string|exists:restaurant_tables,table_id',
            'server_id'  => 'required|string|exists:employees,employee_id',
            'customer_id'=> 'nullable|string|exists:contacts,contact_id',
            'booking_id' => 'nullable|string|exists:bookings,booking_id',
            'notes'      => 'nullable|string',
        ]);

        // SR-13: Active employee only
        $server = Employee::where('employee_id', $request->server_id)->firstOrFail();
        if ($server->status !== 'Aktif') {
            return back()->withInput()->with('error', "Karyawan {$server->name} tidak aktif dan tidak dapat melayani meja.");
        }

        $seq     = TableOrder::max('id') + 1;
        $orderId = 'TO/' . now()->format('Y') . '/' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        $order = TableOrder::create([
            'order_id'    => $orderId,
            'table_id'    => $request->table_id,
            'server_id'   => $request->server_id,
            'customer_id' => $request->customer_id,
            'booking_id'  => $request->booking_id,
            'status'      => 'Open',
            'subtotal'    => 0,
            'pbjt_amount' => 0,
            'gross_amount'=> 0,
            'split_count' => 1,
            'notes'       => $request->notes,
        ]);

        RestaurantTable::where('table_id', $request->table_id)->update(['status' => 'occupied']);

        AuditLog::log('CREATE', 'TableOrder', $orderId, null, null,
            ['table_id' => $request->table_id, 'server_id' => $request->server_id],
            "Table Order {$orderId} dibuka di {$request->table_id}.",
            Auth::user()->name, Auth::user()->role);

        return redirect()->route('table-orders.show', $orderId)->with('success', "Pesanan meja {$request->table_id} dibuka.");
    }

    public function show(string $id)
    {
        $order    = TableOrder::with(['table', 'server', 'booking', 'lines.product', 'invoices'])
            ->where(fn($q) => $q->where('order_id', $id)->orWhere('id', $id))
            ->firstOrFail();
        $products = Product::orderBy('variant_sku')->get();
        $users    = User::whereIn('role', ['owner', 'finance'])->get(); // For discount approval
        return view('table-orders.show', compact('order', 'products', 'users'));
    }

    public function addLine(Request $request, string $id)
    {
        $order = TableOrder::where(fn($q) => $q->where('order_id', $id)->orWhere('id', $id))->firstOrFail();

        if (!in_array($order->status, ['Open'])) {
            return back()->with('error', 'Tidak dapat menambah item ke order yang sudah ditutup.');
        }

        $request->validate([
            'variant_sku' => 'required|string|exists:products,variant_sku',
            'quantity'    => 'required|numeric|min:1',
            'discount'    => 'nullable|numeric|min:0|max:15',
            'approval_id' => 'nullable|exists:users,id',
        ]);

        $discount = (float) ($request->discount ?? 0);

        // SR-08
        if ($discount > 15) {
            return back()->with('error', 'Diskon maksimum adalah 15%.');
        }
        if ($discount > 10) {
            $request->validate(['approval_id' => 'required|exists:users,id']);
            $approver = User::find($request->approval_id);
            if (!in_array($approver->role, ['owner', 'finance'])) {
                return back()->with('error', 'Approval ID harus merujuk ke Manager/Finance/Owner.');
            }
        }

        DB::transaction(function () use ($request, $order, $discount) {
            $product  = Product::where('variant_sku', $request->variant_sku)->firstOrFail();
            $qty      = (float) $request->quantity;
            $price    = (float) $product->sales_price;
            $lineNet  = round($qty * $price * (1 - $discount / 100), 2);
            $linePbjt = round($lineNet * self::PBJT_RATE);

            // SR-09: Check stock
            $loc   = str_starts_with($request->variant_sku, 'BV') ? 'BAR-JKT' : 'KITCH-JKT';
            $stock = StockLevel::where('variant_sku', $request->variant_sku)->where('location_code', $loc)->first();

            if (!$stock || $stock->available < $qty) {
                throw new \Exception("Stok tidak cukup untuk {$product->name}. Tersedia: " . ($stock?->available ?? 0));
            }

            TableOrderLine::create([
                'order_id'    => $order->order_id,
                'variant_sku' => $request->variant_sku,
                'quantity'    => $qty,
                'unit_price'  => $price,
                'subtotal'    => $lineNet,
                'pbjt_amount' => $linePbjt,
                'total_amount'=> $lineNet + $linePbjt,
                'status'      => 'Ordered',
            ]);

            // Reserve stock
            $stock->increment('reserved', $qty);

            // Recalculate order totals
            $this->recalcOrderTotals($order);
        });

        return redirect()->route('table-orders.show', $id)->with('success', "Item ditambahkan ke pesanan.");
    }

    public function serve(Request $request, string $id)
    {
        $order = TableOrder::with('lines')->where(fn($q) => $q->where('order_id', $id)->orWhere('id', $id))->firstOrFail();

        if ($order->status !== 'Open') {
            return back()->with('error', 'Order harus Open untuk diubah ke Served.');
        }

        DB::transaction(function () use ($order) {
            foreach ($order->lines as $line) {
                $loc   = str_starts_with($line->variant_sku, 'BV') ? 'BAR-JKT' : 'KITCH-JKT';
                $stock = StockLevel::where('variant_sku', $line->variant_sku)->where('location_code', $loc)->first();

                if ($stock) {
                    $stock->decrement('on_hand', $line->quantity);
                    $stock->decrement('reserved', min($line->quantity, $stock->reserved));
                }

                $product = \App\Models\Product::where('variant_sku', $line->variant_sku)->first();
                StockMove::create([
                    'move_code'          => 'TBL/' . now()->format('Ymd') . '/' . str_pad(StockMove::count() + 1, 4, '0', STR_PAD_LEFT),
                    'reference_doc_type' => 'Table Order',
                    'reference_doc_id'   => $order->order_id,
                    'from_location'      => $loc,
                    'to_location'        => 'Table',
                    'variant_sku'        => $line->variant_sku,
                    'quantity'           => $line->quantity,
                    'unit_cost'          => $product?->standard_cost ?? 0,
                    'total_value'        => round($line->quantity * ($product?->standard_cost ?? 0), 2),
                    'status'             => 'Done',
                ]);

                $line->update(['status' => 'Served']);
            }

            $order->update(['status' => 'Served']);

            AuditLog::log('SERVE', 'TableOrder', $order->order_id, null, ['status' => 'Open'], ['status' => 'Served'],
                "Table Order {$order->order_id} disajikan.", Auth::user()->name, Auth::user()->role);
        });

        return redirect()->route('table-orders.show', $order->order_id)->with('success', "Pesanan telah disajikan.");
    }

    public function bill(Request $request, string $id)
    {
        $order = TableOrder::with('lines')->where(fn($q) => $q->where('order_id', $id)->orWhere('id', $id))->firstOrFail();

        if (!in_array($order->status, ['Served', 'Open'])) {
            return back()->with('error', 'Order harus Open atau Served untuk dibuat tagihan.');
        }

        if ($order->invoices()->where('status', '!=', 'Cancelled')->exists()) {
            return back()->with('error', 'Invoice sudah ada untuk order ini (idempotency check).');
        }

        DB::transaction(function () use ($order) {
            $seq       = Invoice::max('id') + 1;
            $invoiceId = 'INV/' . now()->format('Y') . '/' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $invoice = Invoice::create([
                'invoice_id'      => $invoiceId,
                'type'            => 'Customer Invoice',
                'partner_id'      => $order->customer_id,
                'source_doc_type' => 'Table Order',
                'source_doc_id'   => $order->order_id,
                'invoice_date'    => now()->toDateString(),
                'status'          => 'Draft',
                'subtotal'        => $order->subtotal,
                'pbjt_amount'     => $order->pbjt_amount,
                'total_amount'    => $order->gross_amount,
                'amount_paid'     => 0,
                'amount_due'      => $order->gross_amount,
                'is_posted'       => false,
            ]);

            foreach ($order->lines as $line) {
                InvoiceLine::create([
                    'invoice_id'   => $invoiceId,
                    'variant_sku'  => $line->variant_sku,
                    'invoiced_qty' => $line->quantity,
                    'unit_price'   => $line->unit_price,
                    'subtotal'     => $line->subtotal,
                    'pbjt_rate'    => self::PBJT_RATE,
                    'pbjt_amount'  => $line->pbjt_amount,
                    'total_amount' => $line->total_amount,
                ]);
                $line->update(['status' => 'Invoiced']);
            }

            $order->update(['status' => 'Billed']);

            AuditLog::log('BILL', 'TableOrder', $order->order_id, $invoiceId,
                ['status' => $order->getOriginal('status')], ['status' => 'Billed', 'invoice_id' => $invoiceId],
                "Invoice {$invoiceId} dibuat untuk Table Order {$order->order_id}.",
                Auth::user()->name, Auth::user()->role);
        });

        return redirect()->route('table-orders.show', $order->order_id)->with('success', "Tagihan berhasil dibuat.");
    }

    public function cancel(Request $request, string $id)
    {
        $order = TableOrder::with('lines')->where(fn($q) => $q->where('order_id', $id)->orWhere('id', $id))->firstOrFail();

        if (in_array($order->status, ['Paid', 'Cancelled'])) {
            return back()->with('error', 'Order ini tidak dapat dibatalkan.');
        }

        DB::transaction(function () use ($order) {
            foreach ($order->lines as $line) {
                $loc = str_starts_with($line->variant_sku, 'BV') ? 'BAR-JKT' : 'KITCH-JKT';
                StockLevel::where('variant_sku', $line->variant_sku)
                    ->where('location_code', $loc)
                    ->decrement('reserved', $line->quantity);
            }

            $order->update(['status' => 'Cancelled']);
            RestaurantTable::where('table_id', $order->table_id)->update(['status' => 'available']);

            AuditLog::log('CANCEL', 'TableOrder', $order->order_id, null,
                ['status' => $order->getOriginal('status')], ['status' => 'Cancelled'],
                "Table Order {$order->order_id} dibatalkan.", Auth::user()->name, Auth::user()->role);
        });

        return redirect()->route('table-orders.show', $id)->with('success', "Pesanan dibatalkan.");
    }

    private function recalcOrderTotals(TableOrder $order): void
    {
        $order->refresh();
        $subtotal  = $order->lines->sum('subtotal');
        $pbjt      = $order->lines->sum('pbjt_amount');
        $order->update(['subtotal' => $subtotal, 'pbjt_amount' => $pbjt, 'gross_amount' => $subtotal + $pbjt]);
    }

    /**
     * SR-23: Set split_count. Jumlah subtotal + PBJT seluruh bagian = order total.
     * Hanya mengatur pembagian tampilan — tidak menduplikasi item atau subtotal.
     */
    public function splitBill(Request $request, string $id)
    {
        $order = TableOrder::where(fn($q) => $q->where('order_id', $id)->orWhere('id', $id))->firstOrFail();

        if (!in_array($order->status, ['Open', 'Served'])) {
            return back()->with('error', 'Split bill hanya dapat diatur saat order masih Open atau Served.');
        }

        if ($order->invoices()->where('status', '!=', 'Cancelled')->exists()) {
            return back()->with('error', 'Invoice sudah dibuat — split bill tidak dapat diubah.');
        }

        $request->validate([
            'split_count' => 'required|integer|min:1|max:10',
        ]);

        $splitCount = (int) $request->split_count;
        $order->update(['split_count' => $splitCount]);

        AuditLog::log('SPLIT_BILL', 'TableOrder', $order->order_id, null,
            ['split_count' => $order->getOriginal('split_count')],
            ['split_count' => $splitCount],
            "Split bill {$order->order_id} diatur menjadi {$splitCount} bagian. Total tetap Rp " . number_format($order->gross_amount, 0, ',', '.') . " (SR-23).",
            Auth::user()->name, Auth::user()->role);

        $perBagian = number_format($order->gross_amount / $splitCount, 0, ',', '.');
        return redirect()->route('table-orders.show', $id)
            ->with('success', "Tagihan dibagi {$splitCount} bagian. Per bagian: Rp {$perBagian} (termasuk PBJT, SR-23).");
    }
}
