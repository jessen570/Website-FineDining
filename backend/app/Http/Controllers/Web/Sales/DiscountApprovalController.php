<?php

namespace App\Http\Controllers\Web\Sales;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DiscountApproval;
use App\Models\Quotation;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscountApprovalController extends Controller
{
    public function index(Request $request)
    {
        $approvals = DiscountApproval::with('customer')
            ->filterStatus($request->status)
            ->search($request->search)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $statuses = ['Pending', 'Approved', 'Rejected'];
        $summary  = [
            'pending'  => DiscountApproval::where('status', 'Pending')->count(),
            'approved' => DiscountApproval::where('status', 'Approved')->count(),
            'rejected' => DiscountApproval::where('status', 'Rejected')->count(),
        ];

        return view('sales.discounts.index', compact('approvals', 'statuses', 'summary'));
    }

    public function show(string $id)
    {
        $approval = DiscountApproval::with(['customer', 'quotation', 'salesOrder'])
            ->where(fn($q) => $q->where('id', $id)->orWhere('approval_code', $id))
            ->firstOrFail();

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

        $request->validate([
            'notes'          => 'nullable|string|max:1000',
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        $notes  = $request->input('notes') ?? $request->input('approval_notes');
        $before = $approval->toArray();

        $approval->update([
            'status'           => 'Approved',
            'approved_by'      => Auth::user()->name,
            'approved_by_role' => Auth::user()->role,
            'approved_at'      => now(),
            'notes'            => $notes ?: $approval->notes,
        ]);

        // Link approver to parent document if applicable
        if (strtolower((string) $approval->document_type) === 'salesorder') {
            SalesOrder::where('order_id', $approval->document_id)
                ->update(['approval_id' => Auth::id()]);
        } elseif (strtolower((string) $approval->document_type) === 'quotation') {
            Quotation::where('quotation_id', $approval->document_id)
                ->update(['approval_id' => Auth::id()]);
        }

        AuditLog::log('APPROVE_DISCOUNT', 'DiscountApproval', $approval->approval_code, null,
            $before, $approval->fresh()->toArray(),
            "Diskon {$approval->discount_percent}% disetujui untuk {$approval->document_id}.",
            Auth::user()->name, Auth::user()->role);

        return redirect()->route('sales.discounts.show', $approval->approval_code)
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

        $request->validate(['rejection_reason' => 'required|string|max:1000']);

        $approval->update([
            'status'           => 'Rejected',
            'approved_by'      => Auth::user()->name,
            'approved_by_role' => Auth::user()->role,
            'approved_at'      => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        AuditLog::log('REJECT_DISCOUNT', 'DiscountApproval', $approval->approval_code, null,
            ['status' => 'Pending'], ['status' => 'Rejected'],
            "Diskon ditolak untuk {$approval->document_id}: {$request->rejection_reason}",
            Auth::user()->name, Auth::user()->role);

        return redirect()->route('sales.discounts.show', $approval->approval_code)
            ->with('success', 'Diskon ditolak.');
    }
}
