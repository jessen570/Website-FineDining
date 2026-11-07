<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DeliveryOrder;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Location;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\StockLevel;
use App\Models\StockMove;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $location = $request->get('location', '');
        $query    = StockLevel::with(['product', 'location']);

        if ($location) {
            $query->where('location_code', $location);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->whereHas('product', fn($p) => $p->where('name', 'like', "%{$q}%")->orWhere('variant_sku', 'like', "%{$q}%"));
        }

        $stockLevels = $query->orderBy('location_code')->orderBy('variant_sku')->get();
        $locations   = Location::all();

        // Summary calculations
        $totalOnHand    = $stockLevels->sum('on_hand');
        $totalReserved  = $stockLevels->sum('reserved');
        $totalAvailable = $stockLevels->sum('available');
        $totalValue     = $stockLevels->reduce(fn($carry, $sl) => $carry + ($sl->on_hand * ($sl->product?->standard_cost ?? 0)), 0);

        // Summary as object for view compatibility
        $summary = (object)[
            'total_on_hand'  => $totalOnHand,
            'total_reserved' => $totalReserved,
            'total_available'=> $totalAvailable,
            'total_value'    => $totalValue,
        ];

        $stocks = $stockLevels;

        return view('inventory.index', compact('stockLevels', 'stocks', 'locations', 'totalOnHand', 'totalReserved', 'totalAvailable', 'totalValue', 'location', 'summary'));
    }

    public function transferForm()
    {
        $locations = Location::all();
        $products  = Product::orderBy('variant_sku')->get();
        $stockData = StockLevel::all()->map(fn($sl) => [
            'location_code' => $sl->location_code,
            'variant_sku'   => $sl->variant_sku,
            'on_hand'       => (float) $sl->on_hand,
            'reserved'      => (float) $sl->reserved,
            'available'     => (float) $sl->available,
        ]);
        return view('inventory.transfer', compact('locations', 'products', 'stockData'));
    }

    public function storeTransfer(Request $request)
    {
        $request->validate([
            'from_location' => 'required|string|exists:locations,location_code',
            'to_location'   => 'required|string|exists:locations,location_code|different:from_location',
            'variant_sku'   => 'required|string|exists:products,variant_sku',
            'quantity'      => 'required|numeric|min:1',
            'notes'         => 'nullable|string',
        ]);

        $qty = (float) $request->quantity;

        // SR-09: Check available stock at source before transaction
        $source = StockLevel::where('location_code', $request->from_location)
            ->where('variant_sku', $request->variant_sku)->first();

        if (!$source || $source->available < $qty) {
            $avail = $source ? $source->available : 0;
            $msg = "Transfer ditolak: Stok tersedia di {$request->from_location} tidak mencukupi (Tersedia: {$avail}, diminta: {$qty}).";
            return back()->withInput()->withErrors(['quantity' => $msg])->with('error', $msg);
        }

        try {
            DB::transaction(function () use ($request, $qty, $source) {
                $product = Product::where('variant_sku', $request->variant_sku)->firstOrFail();

                // SR-21: Decrease source, increase destination
                $source->decrement('on_hand', $qty);

                $dest = StockLevel::firstOrCreate(
                    ['location_code' => $request->to_location, 'variant_sku' => $request->variant_sku],
                    ['on_hand' => 0, 'reserved' => 0]
                );
                $dest->increment('on_hand', $qty);

                // Record stock move
                $moveCode = 'TRF/' . now()->format('Ymd') . '/' . str_pad(StockMove::count() + 1, 4, '0', STR_PAD_LEFT);
                StockMove::create([
                    'move_code'          => $moveCode,
                    'reference_doc_type' => 'Transfer',
                    'reference_doc_id'   => $moveCode,
                    'from_location'      => $request->from_location,
                    'to_location'        => $request->to_location,
                    'variant_sku'        => $request->variant_sku,
                    'quantity'           => $qty,
                    'unit_cost'          => $product->standard_cost,
                    'total_value'        => round($qty * $product->standard_cost, 2),
                    'status'             => 'Done',
                ]);

                AuditLog::log('STOCK_TRANSFER', 'StockMove', $moveCode, null, null,
                    ['from' => $request->from_location, 'to' => $request->to_location, 'sku' => $request->variant_sku, 'qty' => $qty],
                    "Transfer {$qty} unit {$request->variant_sku} dari {$request->from_location} ke {$request->to_location}.",
                    Auth::user()->name, Auth::user()->role);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal memproses transfer: ' . $e->getMessage());
        }

        return redirect()->route('inventory.index')->with('success', "Transfer stok berhasil dilakukan.");
    }

    public function deliveries(Request $request)
    {
        $query = DeliveryOrder::with(['salesOrder.customer', 'lines.product']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $deliveries = $query->orderByDesc('id')->paginate(15)->withQueryString();
        return view('inventory.deliveries', compact('deliveries'));
    }

    public function showDelivery(string $did)
    {
        $delivery = DeliveryOrder::with(['salesOrder.customer', 'lines.product'])
            ->where(fn($q) => $q->where('delivery_id', $did)->orWhere('id', $did))
            ->firstOrFail();
        return view('inventory.delivery_show', compact('delivery'));
    }

    public function validateDelivery(Request $request, string $did)
    {
        $delivery = DeliveryOrder::with(['lines', 'salesOrder.lines'])
            ->where(fn($q) => $q->where('delivery_id', $did)->orWhere('id', $did))
            ->firstOrFail();

        if ($delivery->status !== 'Ready') {
            return back()->with('error', 'Hanya Delivery Order berstatus Ready yang dapat divalidasi.');
        }

        if ($delivery->is_posted) {
            return back()->with('error', 'Delivery Order ini sudah diposting (idempotency check).');
        }

        $request->validate([
            'delivered_qtys'   => 'required|array',
            'delivered_qtys.*' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($delivery, $request) {
                $totalCogs    = 0;
                $hasBackorder = false;

                foreach ($delivery->lines as $line) {
                    $deliveredQty = (float) ($request->delivered_qtys[$line->id] ?? 0);
                    $deliveredQty = min($deliveredQty, $line->ordered_qty - $line->delivered_qty);

                    if ($deliveredQty > 0) {
                        // SR-09: Check available
                        $loc = str_starts_with($line->variant_sku, 'BV') ? 'BAR-JKT' : 'KITCH-JKT';
                        $stock = StockLevel::where('variant_sku', $line->variant_sku)->where('location_code', $loc)->first();

                        if (!$stock || $stock->on_hand < $deliveredQty) {
                            throw new \Exception("Stok tidak cukup untuk {$line->variant_sku} di {$loc}.");
                        }

                        // SR-11: Decrease on_hand and reserved
                        $stock->decrement('on_hand', $deliveredQty);
                        $stock->decrement('reserved', min($deliveredQty, $stock->reserved));

                        // Calculate COGS
                        $cogs = round($deliveredQty * $line->standard_cost, 2);
                        $totalCogs += $cogs;

                        $line->update([
                            'delivered_qty' => $line->delivered_qty + $deliveredQty,
                            'subtotal_cogs' => $line->subtotal_cogs + $cogs,
                        ]);

                        // Update SO line delivered qty
                        SalesOrderLine::where('order_id', $delivery->sales_order_id)
                            ->where('variant_sku', $line->variant_sku)
                            ->increment('delivered_qty', $deliveredQty);

                        // Stock Move
                        $moveCode = 'OUT/' . now()->format('Ymd') . '/' . str_pad(StockMove::count() + 1, 4, '0', STR_PAD_LEFT);
                        StockMove::create([
                            'move_code'          => $moveCode,
                            'reference_doc_type' => 'Delivery Order',
                            'reference_doc_id'   => $delivery->delivery_id,
                            'from_location'      => $loc,
                            'to_location'        => 'Customer',
                            'variant_sku'        => $line->variant_sku,
                            'quantity'           => $deliveredQty,
                            'unit_cost'          => $line->standard_cost,
                            'total_value'        => $cogs,
                            'status'             => 'Done',
                        ]);
                    }

                    if ($line->ordered_qty > $line->delivered_qty + $deliveredQty) {
                        $hasBackorder = true;
                    }
                }

                $delivery->update([
                    'status'             => 'Done',
                    'cost_of_goods_sold' => $delivery->cost_of_goods_sold + $totalCogs,
                    'is_posted'          => true,
                ]);

                // SR-11: Post COGS Journal Entry
                $jeId = 'JE/' . now()->format('Y') . '/' . str_pad(JournalEntry::max('id') + 1, 4, '0', STR_PAD_LEFT);
                $je = JournalEntry::create([
                    'entry_id'        => $jeId,
                    'entry_date'      => now()->toDateString(),
                    'reference_type'  => 'Delivery Order',
                    'reference_id'    => $delivery->delivery_id,
                    'description'     => "COGS untuk DO {$delivery->delivery_id}",
                    'status'          => 'Posted',
                    'total_debit'     => $totalCogs,
                    'total_credit'    => $totalCogs,
                    'is_posted'       => true,
                ]);

                JournalItem::create(['entry_id' => $jeId, 'account_code' => '5010', 'account_name' => 'Harga Pokok Penjualan', 'description' => "COGS {$delivery->delivery_id}", 'debit' => $totalCogs, 'credit' => 0]);
                JournalItem::create(['entry_id' => $jeId, 'account_code' => '1200', 'account_name' => 'Persediaan', 'description' => "Keluarkan persediaan {$delivery->delivery_id}", 'debit' => 0, 'credit' => $totalCogs]);

                // Update SO delivery status
                $so = $delivery->salesOrder;
                $allLines = SalesOrderLine::where('order_id', $so->order_id)->get();
                $allDelivered = $allLines->every(fn($l) => $l->delivered_qty >= $l->ordered_qty);
                $anyDelivered = $allLines->some(fn($l) => $l->delivered_qty > 0);

                $so->update([
                    'delivery_status' => $allDelivered ? 'Delivered' : ($anyDelivered ? 'Partially Delivered' : 'Pending'),
                    'invoice_status'  => 'To Invoice',
                ]);

                // Handle backorder
                if ($hasBackorder) {
                    $boId  = 'WH/OUT/' . str_pad(DeliveryOrder::max('id') + 1, 4, '0', STR_PAD_LEFT);
                    $boDoc = DeliveryOrder::create([
                        'delivery_id'           => $boId,
                        'sales_order_id'        => $delivery->sales_order_id,
                        'status'                => 'Ready',
                        'delivery_date'         => now()->addDay()->toDateString(),
                        'cost_of_goods_sold'    => 0,
                        'is_posted'             => false,
                        'backorder_delivery_id' => $delivery->delivery_id,
                    ]);

                    foreach ($delivery->lines as $line) {
                        $remaining = $line->ordered_qty - $line->delivered_qty;
                        if ($remaining > 0) {
                            \App\Models\DeliveryOrderLine::create([
                                'delivery_id'   => $boId,
                                'variant_sku'   => $line->variant_sku,
                                'ordered_qty'   => $remaining,
                                'delivered_qty' => 0,
                                'backorder_qty' => 0,
                                'standard_cost' => $line->standard_cost,
                                'subtotal_cogs' => 0,
                            ]);
                        }
                    }
                }

                AuditLog::log('VALIDATE', 'DeliveryOrder', $delivery->delivery_id, null,
                    ['status' => 'Ready'], ['status' => 'Done', 'cogs' => $totalCogs],
                    "DO {$delivery->delivery_id} divalidasi. COGS Rp " . number_format($totalCogs),
                    Auth::user()->name, Auth::user()->role);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Validasi gagal: ' . $e->getMessage());
        }

        return redirect()->route('inventory.delivery.show', $did)->with('success', "Delivery Order divalidasi dan COGS diposting.");
    }
}
