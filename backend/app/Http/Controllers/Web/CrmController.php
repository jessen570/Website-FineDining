<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\CrmLead;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CrmController extends Controller
{
    private const STAGES = ['Lead', 'Qualified', 'Proposition', 'Won', 'Lost'];

    public function index(Request $request)
    {
        $query = CrmLead::with('customer');

        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('lead_id', 'like', "%{$q}%");
            });
        }

        $leads  = $query->orderByDesc('created_at')->get();
        $stages = self::STAGES;

        // Group by stage for kanban view
        $kanban = collect(self::STAGES)->mapWithKeys(fn($s) => [
            $s => $leads->where('stage', $s)->values()
        ]);

        return view('crm.index', compact('leads', 'stages', 'kanban'));
    }

    public function create()
    {
        $customers = Contact::where('contact_type', 'Customer')->orderBy('name')->get();
        $stages    = self::STAGES;
        return view('crm.create', compact('customers', 'stages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'customer_id'      => 'required|string|exists:contacts,contact_id',
            'stage'            => 'required|in:Lead,Qualified,Proposition,Won,Lost',
            'expected_revenue' => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string',
        ]);

        // Generate sequential lead_id
        $last = CrmLead::orderByDesc('id')->first();
        $seq  = $last ? ((int) ltrim(str_replace('L', '', $last->lead_id), '0') + 1) : 1;
        $validated['lead_id'] = 'L' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        $lead = CrmLead::create($validated);

        AuditLog::log('CREATE', 'CrmLead', $lead->lead_id, null, null, $validated,
            "Lead {$lead->lead_id} dibuat.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('crm.show', $lead->lead_id)
            ->with('success', "Lead {$lead->lead_id} berhasil dibuat.");
    }

    public function show(string $id)
    {
        $lead      = CrmLead::with('customer')->where('lead_id', $id)->firstOrFail();
        $stages    = self::STAGES;
        $linkedSO  = null;
        if ($lead->converted_opportunity_id) {
            $linkedSO = SalesOrder::where('order_id', $lead->converted_opportunity_id)->first();
        }
        return view('crm.show', compact('lead', 'stages', 'linkedSO'));
    }

    public function edit(string $id)
    {
        $lead      = CrmLead::where('lead_id', $id)->firstOrFail();
        $customers = Contact::where('contact_type', 'Customer')->orderBy('name')->get();
        $stages    = self::STAGES;
        return view('crm.edit', compact('lead', 'customers', 'stages'));
    }

    public function update(Request $request, string $id)
    {
        $lead = CrmLead::where('lead_id', $id)->firstOrFail();

        if ($lead->is_converted) {
            return back()->with('error', 'Lead yang sudah dikonversi tidak dapat diubah.');
        }

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'customer_id'      => 'required|string|exists:contacts,contact_id',
            'stage'            => 'required|in:Lead,Qualified,Proposition,Won,Lost',
            'expected_revenue' => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string',
        ]);

        $before = $lead->toArray();
        $lead->update($validated);

        AuditLog::log('UPDATE', 'CrmLead', $lead->lead_id, null, $before, $validated,
            "Lead {$lead->lead_id} diperbarui.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('crm.show', $lead->lead_id)->with('success', "Lead diperbarui.");
    }

    public function updateStage(Request $request, string $id)
    {
        $lead = CrmLead::where('lead_id', $id)->firstOrFail();

        if ($lead->is_converted) {
            return back()->with('error', 'Lead yang sudah dikonversi tidak dapat diubah.');
        }

        $request->validate(['stage' => 'required|in:Lead,Qualified,Proposition,Won,Lost']);

        $before = ['stage' => $lead->stage];
        $lead->update(['stage' => $request->stage]);

        AuditLog::log('STAGE_CHANGE', 'CrmLead', $lead->lead_id, null, $before,
            ['stage' => $request->stage], "Lead dipindahkan ke {$request->stage}.",
            Auth::user()->name, Auth::user()->role);

        return redirect()->route('crm.show', $lead->lead_id)
            ->with('success', "Stage diperbarui ke {$request->stage}.");
    }

    public function convert(Request $request, string $id)
    {
        $lead = CrmLead::with('customer')->where('lead_id', $id)->firstOrFail();

        if ($lead->is_converted) {
            return back()->with('error', 'Lead ini sudah pernah dikonversi.');
        }

        // Mark lead as Won and converted (only set Won if not already Won/Lost)
        $lead->update([
            'stage'        => in_array($lead->stage, ['Won', 'Lost']) ? $lead->stage : 'Won',
            'is_converted' => true,
        ]);

        AuditLog::log('CONVERT', 'CrmLead', $lead->lead_id, null,
            ['stage' => 'Proposition', 'is_converted' => false],
            ['stage' => 'Won', 'is_converted' => true],
            "Lead {$lead->lead_id} dikonversi. Redirect ke buat SO.",
            Auth::user()->name, Auth::user()->role);

        // Redirect to create a new Sales Order pre-filled with customer
        return redirect()->route('sales.create', ['customer_id' => $lead->customer_id, 'crm_lead_id' => $lead->lead_id])
            ->with('success', "Lead dikonversi. Buat Sales Order untuk {$lead->customer->name}.");
    }
}
