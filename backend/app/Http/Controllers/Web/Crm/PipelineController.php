<?php

namespace App\Http\Controllers\Web\Crm;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CrmLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PipelineController extends Controller
{
    public function index(Request $request)
    {
        $stages = CrmLead::$pipelineStages;

        $query = CrmLead::with(['customer', 'assignedUser']);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($s) use ($q) {
                $s->where('name', 'like', "%{$q}%")
                  ->orWhere('company_name', 'like', "%{$q}%");
            });
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $all = $query->orderByDesc('updated_at')->get()->map(function ($item) use ($stages) {
            // Normalize legacy 'New' or unrecognized stage into 'Lead' so no item is hidden from Kanban yet counted in total
            if (!in_array($item->stage, $stages)) {
                $item->stage = 'Lead';
            }
            return $item;
        });

        // Group by stage for kanban
        $kanban = collect($stages)->mapWithKeys(fn($s) => [
            $s => $all->where('stage', $s)->values()
        ]);

        // Per-stage totals
        $stageTotals = collect($stages)->mapWithKeys(fn($s) => [
            $s => [
                'count'   => $all->where('stage', $s)->count(),
                'revenue' => $all->where('stage', $s)->sum('expected_revenue'),
            ]
        ]);

        // Exact sum of all stages displayed in the summary table
        $grandTotal = $stageTotals->sum('revenue');

        return view('crm.pipeline.index', compact('stages', 'kanban', 'stageTotals', 'grandTotal'));
    }

    public function updateStage(Request $request, string $id)
    {
        $request->validate(['stage' => 'required|in:Lead,Qualified,Proposition,Negotiation,Won,Lost']);

        $lead = CrmLead::where('lead_id', $id)->firstOrFail();
        $before = $lead->stage;
        $lead->update(['stage' => $request->stage]);

        AuditLog::log('PIPELINE_MOVE', 'CrmLead', $id, null,
            ['stage' => $before], ['stage' => $request->stage],
            "Pipeline: {$id} dipindahkan dari {$before} ke {$request->stage}.",
            Auth::user()->name, Auth::user()->role);

        return response()->json(['success' => true]);
    }
}
