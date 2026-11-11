<?php

namespace App\Http\Controllers\Web\Crm;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\CrmLead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OpportunityController extends Controller
{
    public function index(Request $request)
    {
        $query = CrmLead::with(['customer', 'assignedUser'])
            ->where('is_converted', true);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('lead_id', 'like', "%{$q}%")
                    ->orWhere('company_name', 'like', "%{$q}%");
            });
        }
        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }

        $opportunities = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();
        $stages        = CrmLead::$pipelineStages;

        // Stage totals for mini KPIs
        $stageTotals = CrmLead::where('is_converted', true)
            ->selectRaw('stage, COUNT(*) as count, SUM(expected_revenue) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage');

        return view('crm.opportunities.index', compact('opportunities', 'stages', 'stageTotals'));
    }

    public function create(Request $request)
    {
        $customers  = Contact::where('contact_type', 'Customer')->orderBy('name')->get();
        $users      = User::orderBy('name')->get();
        $stages     = CrmLead::$pipelineStages;
        $fromLead   = $request->from_lead ? CrmLead::where('lead_id', $request->from_lead)->first() : null;
        $preCustomer = $request->customer_id;
        return view('crm.opportunities.create', compact('customers', 'users', 'stages', 'fromLead', 'preCustomer'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'company_name'     => 'nullable|string|max:255',
            'customer_id'      => 'nullable|string|exists:contacts,contact_id',
            'from_lead'        => 'nullable|string|exists:crm_leads,lead_id',
            'stage'            => 'required|in:Lead,Qualified,Proposition,Negotiation,Won,Lost',
            'expected_revenue' => 'nullable|numeric|min:0',
            'probability'      => 'nullable|numeric|min:0|max:100',
            'closing_date'     => 'nullable|date',
            'assigned_to'      => 'nullable|exists:users,id',
            'notes'            => 'nullable|string',
        ]);

        // If created from lead, link it
        if ($request->filled('from_lead')) {
            $lead = CrmLead::where('lead_id', $request->from_lead)->first();
            if ($lead) {
                // Update the lead's stage to match
                $lead->update([
                    'is_converted'    => true,
                    'conversion_date' => now()->toDateString(),
                    'stage'           => $validated['stage'],
                    'customer_id'     => $validated['customer_id'] ?? $lead->customer_id,
                ]);

                AuditLog::log('UPDATE', 'CrmLead', $lead->lead_id, null,
                    ['stage' => $lead->getOriginal('stage')],
                    ['stage' => $validated['stage'], 'is_converted' => true],
                    "Lead {$lead->lead_id} stage diperbarui dari konversi Opportunity.",
                    Auth::user()->name, Auth::user()->role);

                return redirect()->route('crm.opportunities.show', $lead->lead_id)
                    ->with('success', "Opportunity dari Lead {$lead->lead_id} berhasil disimpan.");
            }
        }

        // Create a new lead as opportunity
        $maxNum = CrmLead::pluck('lead_id')
            ->map(fn($lid) => (int) preg_replace('/\D/', '', (string) $lid))
            ->max() ?: 0;
        $validated['lead_id']          = 'L' . str_pad($maxNum + 1, 4, '0', STR_PAD_LEFT);
        $validated['customer_id']      = $validated['customer_id'] ?: null;
        $validated['expected_revenue'] = $validated['expected_revenue'] ?? 0;
        $validated['probability']      = $validated['probability'] ?? 0;
        $validated['lead_status']      = 'Qualified';
        $validated['is_converted']     = true;
        $validated['conversion_date']  = now()->toDateString();

        $opp = CrmLead::create($validated);

        AuditLog::log('CREATE', 'CrmLead', $opp->lead_id, null, null, $validated,
            "Opportunity {$opp->lead_id} '{$opp->name}' dibuat.",
            Auth::user()?->name ?? 'System', Auth::user()?->role ?? 'user');

        return redirect()->route('crm.opportunities.show', $opp->lead_id)
            ->with('success', "Opportunity {$opp->lead_id} berhasil dibuat.");
    }

    public function show(string $id)
    {
        $opp = CrmLead::with(['customer', 'assignedUser', 'linkedSalesOrder', 'linkedQuotation'])
            ->where(function ($q) use ($id) {
                $q->where('lead_id', $id)->orWhere('id', $id);
            })->firstOrFail();

        $auditLogs = AuditLog::where('document_type', 'CrmLead')
            ->where(function ($q) use ($id, $opp) {
                $q->where('document_id', $id)->orWhere('document_id', $opp->lead_id);
            })
            ->orderByDesc('created_at')->get();

        $stages = CrmLead::$pipelineStages;

        return view('crm.opportunities.show', compact('opp', 'auditLogs', 'stages'));
    }

    public function edit(string $id)
    {
        $opp       = CrmLead::where('lead_id', $id)->orWhere('id', $id)->firstOrFail();
        $customers = Contact::where('contact_type', 'Customer')->orderBy('name')->get();
        $users     = User::orderBy('name')->get();
        $stages    = CrmLead::$pipelineStages;
        return view('crm.opportunities.edit', compact('opp', 'customers', 'users', 'stages'));
    }

    public function update(Request $request, string $id)
    {
        $opp = CrmLead::where('lead_id', $id)->orWhere('id', $id)->firstOrFail();

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'company_name'     => 'nullable|string|max:255',
            'customer_id'      => 'nullable|string|exists:contacts,contact_id',
            'stage'            => 'required|in:Lead,Qualified,Proposition,Negotiation,Won,Lost',
            'expected_revenue' => 'nullable|numeric|min:0',
            'probability'      => 'nullable|numeric|min:0|max:100',
            'closing_date'     => 'nullable|date',
            'assigned_to'      => 'nullable|exists:users,id',
            'notes'            => 'nullable|string',
        ]);

        $validated['customer_id']      = $validated['customer_id'] ?: null;
        $validated['expected_revenue'] = $validated['expected_revenue'] ?? 0;
        $validated['probability']      = $validated['probability'] ?? 0;

        $before = $opp->toArray();
        $opp->update($validated);

        AuditLog::log('UPDATE', 'CrmLead', $opp->lead_id, null, $before, $validated,
            "Opportunity {$opp->lead_id} diperbarui.", Auth::user()?->name ?? 'System', Auth::user()?->role ?? 'user');

        return redirect()->route('crm.opportunities.show', $opp->lead_id)
            ->with('success', 'Opportunity berhasil diperbarui.');
    }

    public function updateStage(Request $request, string $id)
    {
        $opp = CrmLead::where('lead_id', $id)->orWhere('id', $id)->firstOrFail();
        $request->validate(['stage' => 'required|in:Lead,Qualified,Proposition,Negotiation,Won,Lost']);

        $before = ['stage' => $opp->stage];
        $opp->update(['stage' => $request->stage]);

        AuditLog::log('STAGE_CHANGE', 'CrmLead', $opp->lead_id, null, $before,
            ['stage' => $request->stage], "Opportunity dipindahkan ke {$request->stage}.",
            Auth::user()?->name ?? 'System', Auth::user()?->role ?? 'user');

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'stage' => $request->stage]);
        }

        return back()->with('success', "Tahapan peluang diubah menjadi {$request->stage}.");
    }

    public function markWon(Request $request, string $id)
    {
        $opp = CrmLead::where('lead_id', $id)->orWhere('id', $id)->firstOrFail();
        $opp->update(['stage' => 'Won']);

        AuditLog::log('MARK_WON', 'CrmLead', $opp->lead_id, null,
            ['stage' => $opp->getOriginal('stage')], ['stage' => 'Won'],
            "Opportunity {$opp->lead_id} ditandai Won.",
            Auth::user()->name, Auth::user()->role);

        return redirect()->route('crm.opportunities.show', $opp->lead_id)
            ->with('success', 'Opportunity ditandai sebagai Won.');
    }

    public function markLost(Request $request, string $id)
    {
        $opp = CrmLead::where('lead_id', $id)->orWhere('id', $id)->firstOrFail();
        $opp->update(['stage' => 'Lost']);

        AuditLog::log('MARK_LOST', 'CrmLead', $opp->lead_id, null,
            ['stage' => $opp->getOriginal('stage')], ['stage' => 'Lost'],
            "Opportunity {$opp->lead_id} ditandai Lost.",
            Auth::user()->name, Auth::user()->role);

        return redirect()->route('crm.opportunities.show', $opp->lead_id)
            ->with('success', 'Opportunity ditandai sebagai Lost.');
    }
}
