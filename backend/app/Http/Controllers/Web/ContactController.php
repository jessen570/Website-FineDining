<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\VendorPricelist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $role = strtolower(Auth::user()->role ?? '');
        $canManageVendors = $this->canManageVendors();

        if ($role === 'procurement') {
            $activeType = 'Vendor';
        } else {
            $reqType = $request->input('type');
            $activeType = in_array($reqType, ['Customer', 'Vendor'], true) ? $reqType : 'Customer';
        }

        $query = Contact::where('contact_type', $activeType);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('contact_id', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%");
            });
        }

        $contacts = $query->orderBy('contact_id')->paginate(15)->withQueryString();
        $customerCount = Contact::where('contact_type', 'Customer')->count();
        $vendorCount   = Contact::where('contact_type', 'Vendor')->count();
        $pricelistCounts = ($activeType === 'Vendor')
            ? VendorPricelist::selectRaw('vendor_id, COUNT(*) as cnt')->groupBy('vendor_id')->pluck('cnt', 'vendor_id')
            : collect();

        return view('contacts.index', compact(
            'contacts',
            'activeType',
            'customerCount',
            'vendorCount',
            'pricelistCounts',
            'canManageVendors'
        ));
    }

    public function create()
    {
        $products = Product::orderBy('variant_sku')->get();
        $canManageVendors = $this->canManageVendors();
        return view('contacts.create', compact('products', 'canManageVendors'));
    }

    public function store(Request $request)
    {
        if ($request->contact_type === 'Vendor' && !$this->canManageVendors()) {
            return back()->withInput()->with('error', 'Hanya role Procurement atau Owner yang dapat mendaftarkan Vendor baru.');
        }

        $validated = $request->validate([
            'contact_id'    => 'required|string|unique:contacts,contact_id',
            'name'          => 'required|string|max:255',
            'contact_type'  => 'required|in:Customer,Vendor',
            'customer_type' => 'nullable|in:Individu,Perusahaan',
            'city'          => 'nullable|string|max:100',
            'credit_limit'  => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|max:100',
            'opening_ar'    => 'nullable|numeric|min:0',
            'opening_ap'    => 'nullable|numeric|min:0',
            'vendor_items'  => 'nullable|array',
            'vendor_items.*.variant_sku' => 'nullable|string|exists:products,variant_sku',
            'vendor_items.*.unit_purchase_price' => 'nullable|numeric|min:0',
        ]);

        $contactData = $this->normalizeContactData(collect($validated)->except(['vendor_items'])->all());
        $vendorItems = $request->input('vendor_items', []);

        $contact = DB::transaction(function () use ($contactData, $vendorItems) {
            $contact = Contact::create($contactData);

            if ($contact->contact_type === 'Vendor' && !empty($vendorItems)) {
                foreach ($vendorItems as $item) {
                    if (empty($item['variant_sku'])) {
                        continue;
                    }
                    VendorPricelist::updateOrCreate(
                        ['vendor_id' => $contact->contact_id, 'variant_sku' => $item['variant_sku']],
                        ['unit_purchase_price' => (float) ($item['unit_purchase_price'] ?? 0), 'currency' => 'IDR', 'min_qty' => 1]
                    );
                }
            }

            AuditLog::log('CREATE', 'Contact', $contact->contact_id, null, null, $contactData,
                "Kontak {$contact->name} ditambahkan.", Auth::user()->name, Auth::user()->role);

            return $contact;
        });

        return redirect()->route('contacts.show', $contact->contact_id)
            ->with('success', "Kontak {$contact->name} berhasil ditambahkan.");
    }

    public function show(string $id)
    {
        $contact = Contact::where(fn($q) => $q->where('contact_id', $id)->orWhere('id', $id))->firstOrFail();
        $exposure = ($contact->contact_type === 'Customer') ? $contact->credit_exposure : null;
        $pricelists = ($contact->contact_type === 'Vendor')
            ? VendorPricelist::with('product')->where('vendor_id', $contact->contact_id)->orderBy('variant_sku')->get()
            : collect();
        return view('contacts.show', compact('contact', 'exposure', 'pricelists'));
    }

    public function edit(string $id)
    {
        $contact = Contact::where(fn($q) => $q->where('contact_id', $id)->orWhere('id', $id))->firstOrFail();
        if ($contact->contact_type === 'Vendor' && !$this->canManageVendors()) {
            return redirect()->route('contacts.show', $contact->contact_id)
                ->with('error', 'Hanya role Procurement atau Owner yang dapat mengubah data Vendor.');
        }
        $products = Product::orderBy('variant_sku')->get();
        $pricelists = ($contact->contact_type === 'Vendor')
            ? VendorPricelist::where('vendor_id', $contact->contact_id)->orderBy('variant_sku')->get()
            : collect();
        $canManageVendors = $this->canManageVendors();
        return view('contacts.edit', compact('contact', 'products', 'pricelists', 'canManageVendors'));
    }

    public function update(Request $request, string $id)
    {
        $contact = Contact::where(fn($q) => $q->where('contact_id', $id)->orWhere('id', $id))->firstOrFail();

        if (($contact->contact_type === 'Vendor' || $request->contact_type === 'Vendor') && !$this->canManageVendors()) {
            return back()->withInput()->with('error', 'Hanya role Procurement atau Owner yang dapat mengelola Vendor.');
        }

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'contact_type'  => 'required|in:Customer,Vendor',
            'customer_type' => 'nullable|in:Individu,Perusahaan',
            'city'          => 'nullable|string|max:100',
            'credit_limit'  => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|max:100',
            'opening_ar'    => 'nullable|numeric|min:0',
            'opening_ap'    => 'nullable|numeric|min:0',
            'vendor_items'  => 'nullable|array',
            'vendor_items.*.variant_sku' => 'nullable|string|exists:products,variant_sku',
            'vendor_items.*.unit_purchase_price' => 'nullable|numeric|min:0',
        ]);

        $contactData = $this->normalizeContactData(collect($validated)->except(['vendor_items'])->all());
        $vendorItems = $request->input('vendor_items', []);

        DB::transaction(function () use ($contact, $contactData, $vendorItems) {
            $before = $contact->toArray();
            $contact->update($contactData);

            if ($contact->contact_type === 'Vendor') {
                VendorPricelist::where('vendor_id', $contact->contact_id)->delete();
                foreach ($vendorItems as $item) {
                    if (empty($item['variant_sku'])) {
                        continue;
                    }
                    VendorPricelist::updateOrCreate(
                        ['vendor_id' => $contact->contact_id, 'variant_sku' => $item['variant_sku']],
                        ['unit_purchase_price' => (float) ($item['unit_purchase_price'] ?? 0), 'currency' => 'IDR', 'min_qty' => 1]
                    );
                }
            }

            AuditLog::log('UPDATE', 'Contact', $contact->contact_id, null, $before, $contactData,
                "Kontak {$contact->name} diperbarui.", Auth::user()->name, Auth::user()->role);
        });

        return redirect()->route('contacts.show', $contact->contact_id)
            ->with('success', "Kontak berhasil diperbarui.");
    }

    public function destroy(string $id)
    {
        $contact = Contact::where(fn($q) => $q->where('contact_id', $id)->orWhere('id', $id))->firstOrFail();
        $role = strtolower(Auth::user()->role ?? '');

        if ($contact->contact_type === 'Vendor' && !$this->canManageVendors()) {
            return back()->with('error', 'Hanya role Procurement atau Owner yang dapat menghapus Vendor.');
        }

        if ($contact->contact_type === 'Customer' && !in_array($role, ['owner', 'finance', 'host'], true)) {
            return back()->with('error', 'Anda tidak memiliki izin menghapus Pelanggan.');
        }

        if ($contact->contact_type === 'Vendor') {
            $hasPo   = PurchaseOrder::where('vendor_id', $contact->contact_id)->exists();
            $hasBill = Invoice::where('partner_id', $contact->contact_id)->exists();
            if ($hasPo || $hasBill) {
                return back()->with('error', "Vendor {$contact->name} tidak dapat dihapus karena sudah memiliki riwayat Purchase Order / Tagihan.");
            }
        } else {
            $hasSo  = SalesOrder::where('customer_id', $contact->contact_id)->exists();
            $hasInv = Invoice::where('partner_id', $contact->contact_id)->exists();
            if ($hasSo || $hasInv) {
                return back()->with('error', "Pelanggan {$contact->name} tidak dapat dihapus karena sudah memiliki riwayat Sales Order / Faktur.");
            }
        }

        $type = $contact->contact_type;
        $name = $contact->name;

        DB::transaction(function () use ($contact, $type, $name) {
            $before = $contact->toArray();
            if ($type === 'Vendor') {
                VendorPricelist::where('vendor_id', $contact->contact_id)->delete();
            }
            AuditLog::log(
                'DELETE',
                'Contact',
                $contact->contact_id,
                null,
                $before,
                null,
                "Kontak {$name} ({$type}) dihapus.",
                Auth::user()->name,
                Auth::user()->role
            );
            $contact->delete();
        });

        return redirect()->route('contacts.index', ['type' => $type])
            ->with('success', "{$type} {$name} berhasil dihapus.");
    }

    private function canManageVendors(): bool
    {
        return in_array(strtolower(Auth::user()->role ?? ''), ['owner', 'procurement'], true);
    }

    private function normalizeContactData(array $data): array
    {
        $data['payment_terms'] = !empty($data['payment_terms']) ? $data['payment_terms'] : 'Tunai';
        $data['credit_limit']  = (float) ($data['credit_limit'] ?? 0);
        $data['opening_ar']    = (float) ($data['opening_ar'] ?? 0);
        $data['opening_ap']    = (float) ($data['opening_ap'] ?? 0);

        if (($data['contact_type'] ?? '') === 'Vendor') {
            $data['customer_type'] = null;
            $data['credit_limit']  = 0;
            $data['opening_ar']    = 0;
        } else {
            $data['opening_ap']    = 0;
        }

        return $data;
    }
}
