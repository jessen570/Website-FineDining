<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Owner/GM has overarching superuser access
        if (strtolower($user->role) === 'owner') {
            return $next($request);
        }

        $allowedRoles = array_map('strtolower', $roles);

        if (!in_array(strtolower($user->role), $allowedRoles)) {
            AuditLog::log(
                'ACCESS_DENIED', 'Route',
                $request->path(), $request->method(),
                ['user_role' => $user->role],
                ['required_roles' => $roles],
                "Unauthorized access attempt by {$user->name} ({$user->role}) to {$request->method()} {$request->path()}",
                $user->name, $user->role
            );

            // API requests → JSON 403
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Access denied. Role '{$user->role}' is not authorized.",
                ], 403);
            }

            // Web requests → abort dengan halaman 403 (ada error page dari teman)
            abort(403, "Akses ditolak. Peran '{$user->role}' tidak memiliki izin untuk halaman ini. (SR-14)");
        }

        return $next($request);
    }
}
