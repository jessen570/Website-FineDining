<?php

namespace App\Http\Controllers\Web\Sales;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DiscountApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscountApprovalController extends Controller
{
    public function index(Request $request)
    {
        $query = DiscountApproval::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('approval_code', 'like', "%{$q}%")
                    ->orWhere('document_id', 'like', "%{$q}%")
                    ->orWhere('requested_by', 'like', "%{$q}%");
            });
        }

        $approvals = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $statuses  = ['Pending', 'Approved', 'Rejected'];

        return view('sales.discounts.index', compact('approvals', 'statuses'));
    }

    public function show(string $id)
    {
        $approval = DiscountApproval::where(fn($q) => $q->where('id', $id)->orWhere('approval_code', $id))->firstOrFail();
        return view('sales.discounts.show', compact('approval'));
    }

    public function approve(Request $request, string $id)
    {
        // Only owner/finance can approve
        if (!in_array(Auth::user()->role, ['owner', 'finance'])) {
            return back()->with('error', 'Hanya Owner atau Finance yang dapat menyetujui diskon.');
        }

        $approval = DiscountApproval::where(fn($q) => $q->where('id', $id)->orWhere('approval_code', $id))->firstOrFail();

        if ($approval->status !== 'Pending') {
            return back()->with('error', 'Permintaan ini sudah diproses.');
        }

        $request->validate(['notes' => 'nullable|string']);

        $before = $approval->toArray();
        $approval->update([
            'status'          => 'Approved',
            'approved_by'     => Auth::user()->name,
            'approved_by_role'=> Auth::user()->role,
            'approved_at'     => now(),
            'notes'           => $request->notes,
        ]);

        AuditLog::log('APPROVE_DISCOUNT', 'DiscountApproval', $approval->approval_code, null,
            $before, $approval->fresh()->toArray(),
            "Diskon {$approval->discount_percent}% disetujui untuk {$approval->document_id}.",
            Auth::user()->name, Auth::user()->role);

        return redirect()->route('sales.discounts.show', $id)
            ->with('success', 'Diskon disetujui.');
    }

    public function reject(Request $request, string $id)
    {
        if (!in_array(Auth::user()->role, ['owner', 'finance'])) {
            return back()->with('error', 'Hanya Owner atau Finance yang dapat menolak diskon.');
        }

        $approval = DiscountApproval::where(fn($q) => $q->where('id', $id)->orWhere('approval_code', $id))->firstOrFail();

        if ($approval->status !== 'Pending') {
            return back()->with('error', 'Permintaan ini sudah diproses.');
        }

        $request->validate(['rejection_reason' => 'required|string']);

        $approval->update([
            'status'           => 'Rejected',
            'approved_by'      => Auth::user()->name,
            'approved_by_role' => Auth::user()->role,
            'approved_at'      => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        AuditLog::log('REJECT_DISCOUNT', 'DiscountApproval', $approval->approval_code, null,
            ['status' => 'Pending'], ['status' => 'Rejected'],
            "Diskon ditolak: {$request->rejection_reason}",
            Auth::user()->name, Auth::user()->role);

        return redirect()->route('sales.discounts.show', $id)
            ->with('success', 'Diskon ditolak.');
    }
}
