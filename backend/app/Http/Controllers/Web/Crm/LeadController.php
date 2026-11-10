<?php

namespace App\Http\Controllers\Web\Crm;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\CrmLead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = CrmLead::with(['customer', 'assignedUser']);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('lead_id', 'like', "%{$q}%")
                    ->orWhere('company_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('lead_status', $request->status);
        }
        if ($request->filled('source')) {
            $query->where('lead_source', $request->source);
        }
        if ($request->filled('converted')) {
            $query->where('is_converted', $request->converted === 'yes');
        }

        $leads    = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $users    = User::orderBy('name')->get();
        $sources  = CrmLead::$leadSources;
        $statuses = CrmLead::$leadStatuses;

        return view('crm.leads.index', compact('leads', 'users', 'sources', 'statuses'));
    }

    public function create()
    {
        $customers = Contact::where('contact_type', 'Customer')->orderBy('name')->get();
        $users     = User::whereIn('role', ['owner', 'finance', 'sales', 'server'])->orderBy('name')->get();
        $sources   = CrmLead::$leadSources;
        $statuses  = CrmLead::$leadStatuses;
        $stages    = CrmLead::$pipelineStages;
        return view('crm.leads.create', compact('customers', 'users', 'sources', 'statuses', 'stages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'company_name'     => 'nullable|string|max:255',
            'contact_person'   => 'nullable|string|max:255',
            'email'            => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:50',
            'customer_id'      => 'nullable|string|exists:contacts,contact_id',
            'lead_source'      => 'nullable|string',
            'lead_status'      => 'required|in:New,Contacted,Qualified,Unqualified',
            'stage'            => 'required|in:Lead,Qualified,Proposition,Negotiation,Won,Lost',
            'expected_revenue' => 'nullable|numeric|min:0',
            'probability'      => 'nullable|numeric|min:0|max:100',
            'closing_date'     => 'nullable|date|after_or_equal:today',
            'assigned_to'      => 'nullable|exists:users,id',
            'notes'            => 'nullable|string',
        ]);

        // Generate lead_id
        $last = CrmLead::orderByDesc('id')->first();
        $seq  = $last ? ((int) ltrim(str_replace('L', '', $last->lead_id), '0') + 1) : 1;
        $validated['lead_id'] = 'L' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        $lead = CrmLead::create($validated);

        AuditLog::log('CREATE', 'CrmLead', $lead->lead_id, null, null, $validated,
            "Lead {$lead->lead_id} '{$lead->name}' dibuat.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('crm.leads.show', $lead->lead_id)
            ->with('success', "Lead {$lead->lead_id} berhasil dibuat.");
    }

    public function show(string $id)
    {
        $lead = CrmLead::with(['customer', 'assignedUser', 'linkedSalesOrder', 'linkedQuotation'])
            ->where(function ($q) use ($id) {
                $q->where('lead_id', $id)->orWhere('id', $id);
            })->firstOrFail();

        $auditLogs = AuditLog::where('document_type', 'CrmLead')
            ->where(function ($q) use ($id, $lead) {
                $q->where('document_id', $id)->orWhere('document_id', $lead->lead_id);
            })
            ->orderByDesc('created_at')->get();

        return view('crm.leads.show', compact('lead', 'auditLogs'));
    }

    public function edit(string $id)
    {
        $lead      = CrmLead::where('lead_id', $id)->orWhere('id', $id)->firstOrFail();
        $customers = Contact::where('contact_type', 'Customer')->orderBy('name')->get();
        $users     = User::orderBy('name')->get();
        $sources   = CrmLead::$leadSources;
        $statuses  = CrmLead::$leadStatuses;
        $stages    = CrmLead::$pipelineStages;
        return view('crm.leads.edit', compact('lead', 'customers', 'users', 'sources', 'statuses', 'stages'));
    }

    public function update(Request $request, string $id)
    {
        $lead = CrmLead::where('lead_id', $id)->orWhere('id', $id)->firstOrFail();

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'company_name'     => 'nullable|string|max:255',
            'contact_person'   => 'nullable|string|max:255',
            'email'            => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:50',
            'customer_id'      => 'nullable|string|exists:contacts,contact_id',
            'lead_source'      => 'nullable|string',
            'lead_status'      => 'required|in:New,Contacted,Qualified,Unqualified',
            'stage'            => 'required|in:Lead,Qualified,Proposition,Negotiation,Won,Lost',
            'expected_revenue' => 'nullable|numeric|min:0',
            'probability'      => 'nullable|numeric|min:0|max:100',
            'closing_date'     => 'nullable|date',
            'assigned_to'      => 'nullable|exists:users,id',
            'notes'            => 'nullable|string',
        ]);

        $before = $lead->toArray();
        $lead->update($validated);

        AuditLog::log('UPDATE', 'CrmLead', $lead->lead_id, null, $before, $validated,
            "Lead {$lead->lead_id} diperbarui.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('crm.leads.show', $lead->lead_id)
            ->with('success', 'Lead berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $lead = CrmLead::where('lead_id', $id)->orWhere('id', $id)->firstOrFail();

        if ($lead->is_converted) {
            return back()->with('error', 'Lead yang sudah dikonversi tidak dapat dihapus.');
        }

        AuditLog::log('DELETE', 'CrmLead', $lead->lead_id, null, $lead->toArray(), null,
            "Lead {$lead->lead_id} dihapus.", Auth::user()->name, Auth::user()->role);

        $lead->delete();

        return redirect()->route('crm.leads.index')->with('success', 'Lead berhasil dihapus.');
    }

    public function convert(Request $request, string $id)
    {
        $lead = CrmLead::with('customer')->where('lead_id', $id)->orWhere('id', $id)->firstOrFail();

        if ($lead->is_converted) {
            return back()->with('error', 'Lead ini sudah pernah dikonversi ke Opportunity (SR-24: tidak ada duplikasi).');
        }

        // Idempotency: langsung buat Opportunity sekaligus (SR-24 — tidak ada duplikasi)
        // Opportunity di sistem ini diwakili oleh CrmLead yang is_converted=true + stage Qualified ke atas
        DB::transaction(function () use ($lead) {
            $lead->update([
                'is_converted'    => true,
                'conversion_date' => now()->toDateString(),
                'stage'           => in_array($lead->stage, ['Won', 'Lost']) ? $lead->stage : 'Qualified',
                'lead_status'     => 'Qualified',
            ]);

            AuditLog::log('CONVERT_TO_OPPORTUNITY', 'CrmLead', $lead->lead_id, null,
                ['is_converted' => false, 'stage' => $lead->getOriginal('stage')],
                ['is_converted' => true, 'stage' => 'Qualified', 'conversion_date' => now()->toDateString()],
                "Lead {$lead->lead_id} '{$lead->name}' berhasil dikonversi menjadi Opportunity (stage: Qualified). Expected revenue: Rp " . number_format((float)$lead->expected_revenue, 0, ',', '.') . ".",
                Auth::user()->name, Auth::user()->role);
        });

        return redirect()->route('crm.opportunities.show', $lead->lead_id)
            ->with('success', "Lead {$lead->lead_id} berhasil dikonversi menjadi Opportunity. Stage: Qualified, Expected Revenue: Rp " . number_format((float)$lead->fresh()->expected_revenue, 0, ',', '.') . ".");
    }
}
