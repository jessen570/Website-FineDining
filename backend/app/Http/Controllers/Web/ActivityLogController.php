<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    private const CREATE_ACTIONS = ['CREATE'];

    private const UPDATE_ACTIONS = [
        'UPDATE', 'CONFIRM', 'SEND', 'VALIDATE', 'POST', 'PAYMENT',
        'APPROVE_DISCOUNT', 'REJECT_DISCOUNT', 'STAGE_CHANGE',
        'PIPELINE_MOVE', 'CONVERT_TO_OPPORTUNITY', 'CONVERT_TO_SO',
        'MARK_WON', 'MARK_LOST', 'CHECKIN', 'SERVE', 'SPLIT_BILL', 'BILL',
        'TRANSFER', 'CREDIT_VALIDATION',
    ];

    private const DELETE_ACTIONS = ['DELETE', 'CANCEL'];

    private const SESSION_ACTIONS = ['LOGIN', 'LOGOUT'];

    private const SECURITY_ACTIONS = ['ACCESS_DENIED', 'ATTEMPT_PRICE_EDIT', 'CREDIT_BLOCK'];

    public function index(Request $request)
    {
        $query = AuditLog::query();

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('actor_name', 'like', "%{$q}%")
                    ->orWhere('document_id', 'like', "%{$q}%")
                    ->orWhere('document_type', 'like', "%{$q}%")
                    ->orWhere('message', 'like', "%{$q}%")
                    ->orWhere('reference_code', 'like', "%{$q}%");
            });
        }

        if ($request->filled('actor')) {
            $query->where('actor_name', $request->actor);
        }

        if ($request->filled('doc_type')) {
            $query->where('document_type', $request->doc_type);
        }

        if ($request->filled('category')) {
            match ($request->category) {
                'view'     => $query->where('action', 'VIEW'),
                'create'   => $query->whereIn('action', self::CREATE_ACTIONS),
                'update'   => $query->whereIn('action', self::UPDATE_ACTIONS),
                'delete'   => $query->whereIn('action', self::DELETE_ACTIONS),
                'session'  => $query->whereIn('action', self::SESSION_ACTIONS),
                'security' => $query->whereIn('action', self::SECURITY_ACTIONS),
                default    => null,
            };
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(25)->withQueryString();

        $summary = [
            'total'     => AuditLog::count(),
            'today'     => AuditLog::whereDate('created_at', now()->toDateString())->count(),
            'mutations' => AuditLog::whereIn('action', array_merge(self::CREATE_ACTIONS, self::UPDATE_ACTIONS, self::DELETE_ACTIONS))->count(),
            'views'     => AuditLog::where('action', 'VIEW')->count(),
            'security'  => AuditLog::whereIn('action', self::SECURITY_ACTIONS)->count(),
        ];

        $actors = AuditLog::selectRaw('actor_name, MAX(actor_role) as actor_role, COUNT(*) as total_actions, MAX(created_at) as last_active')
            ->groupBy('actor_name')
            ->orderByDesc('last_active')
            ->get();

        $docTypes = AuditLog::select('document_type')
            ->distinct()
            ->orderBy('document_type')
            ->pluck('document_type');

        return view('activity-logs.index', compact('logs', 'summary', 'actors', 'docTypes'));
    }
}

