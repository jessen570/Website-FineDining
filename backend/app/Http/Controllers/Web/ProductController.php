<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\Product;
use App\Models\StockLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('stockLevels');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('variant_sku', 'like', "%{$q}%");
            });
        }

        if ($request->filled('category')) {
            if ($request->category === 'food') {
                $query->where('variant_sku', 'like', 'FD-%');
            } elseif ($request->category === 'beverage') {
                $query->where('variant_sku', 'like', 'BV-%');
            }
        }

        $products = $query->orderBy('variant_sku')->paginate(20)->withQueryString();
        $vendors  = Contact::where('contact_type', 'Vendor')->orderBy('name')->get();

        return view('products.index', compact('products', 'vendors'));
    }

    public function create()
    {
        $vendors = Contact::where('contact_type', 'Vendor')->orderBy('name')->get();
        return view('products.create', compact('vendors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'variant_sku'       => 'required|string|unique:products,variant_sku',
            'name'              => 'required|string|max:255',
            'size'              => 'nullable|string|max:50',
            'color'             => 'nullable|string|max:50',
            'unit'              => 'required|string|max:20',
            'sales_price'       => 'required|numeric|min:0',
            'standard_cost'     => 'required|numeric|min:0',
            'default_vendor_id' => 'nullable|string|exists:contacts,contact_id',
        ]);

        $product = Product::create($validated);

        AuditLog::log('CREATE', 'Product', $product->variant_sku, null, null, $validated,
            "Produk {$product->name} ditambahkan.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('products.show', $product->variant_sku)
            ->with('success', "Produk {$product->name} berhasil ditambahkan.");
    }

    public function show(string $id)
    {
        $product = Product::with(['stockLevels.location', 'pricelists'])
            ->where(fn($q) => $q->where('variant_sku', $id)->orWhere('id', $id))
            ->firstOrFail();
        return view('products.show', compact('product'));
    }

    public function edit(string $id)
    {
        $product = Product::where(fn($q) => $q->where('variant_sku', $id)->orWhere('id', $id))->firstOrFail();
        $vendors = Contact::where('contact_type', 'Vendor')->orderBy('name')->get();
        return view('products.edit', compact('product', 'vendors'));
    }

    public function update(Request $request, string $id)
    {
        $product = Product::where(fn($q) => $q->where('variant_sku', $id)->orWhere('id', $id))->firstOrFail();

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'size'              => 'nullable|string|max:50',
            'color'             => 'nullable|string|max:50',
            'unit'              => 'required|string|max:20',
            'sales_price'       => 'required|numeric|min:0',
            'standard_cost'     => 'required|numeric|min:0',
            'default_vendor_id' => 'nullable|string|exists:contacts,contact_id',
        ]);

        $before = $product->toArray();
        $product->update($validated);

        AuditLog::log('UPDATE', 'Product', $product->variant_sku, null, $before, $validated,
            "Produk {$product->name} diperbarui.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('products.show', $product->variant_sku)
            ->with('success', "Produk berhasil diperbarui.");
    }
}
