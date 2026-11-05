<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::query();

        if ($request->filled('type')) {
            $query->where('contact_type', $request->type);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('contact_id', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%");
            });
        }

        $contacts = $query->orderBy('contact_id')->paginate(15)->withQueryString();

        return view('contacts.index', compact('contacts'));
    }

    public function create()
    {
        return view('contacts.create');
    }

    public function store(Request $request)
    {
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
        ]);

        $contact = Contact::create($validated);

        AuditLog::log('CREATE', 'Contact', $contact->contact_id, null, null, $validated,
            "Kontak {$contact->name} ditambahkan.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('contacts.show', $contact->contact_id)
            ->with('success', "Kontak {$contact->name} berhasil ditambahkan.");
    }

    public function show(string $id)
    {
        $contact = Contact::where(fn($q) => $q->where('contact_id', $id)->orWhere('id', $id))->firstOrFail();
        $exposure = ($contact->contact_type === 'Customer') ? $contact->credit_exposure : null;
        return view('contacts.show', compact('contact', 'exposure'));
    }

    public function edit(string $id)
    {
        $contact = Contact::where(fn($q) => $q->where('contact_id', $id)->orWhere('id', $id))->firstOrFail();
        return view('contacts.edit', compact('contact'));
    }

    public function update(Request $request, string $id)
    {
        $contact = Contact::where(fn($q) => $q->where('contact_id', $id)->orWhere('id', $id))->firstOrFail();

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'contact_type'  => 'required|in:Customer,Vendor',
            'customer_type' => 'nullable|in:Individu,Perusahaan',
            'city'          => 'nullable|string|max:100',
            'credit_limit'  => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|max:100',
            'opening_ar'    => 'nullable|numeric|min:0',
            'opening_ap'    => 'nullable|numeric|min:0',
        ]);

        $before = $contact->toArray();
        $contact->update($validated);

        AuditLog::log('UPDATE', 'Contact', $contact->contact_id, null, $before, $validated,
            "Kontak {$contact->name} diperbarui.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('contacts.show', $contact->contact_id)
            ->with('success', "Kontak berhasil diperbarui.");
    }
}
