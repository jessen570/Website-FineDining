<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please log in.',
            ], 401);
        }

        // Owner/GM has overarching superuser access
        if (strtolower($user->role) === 'owner') {
            return $next($request);
        }

        $allowedRoles = array_map('strtolower', $roles);

        if (!in_array(strtolower($user->role), $allowedRoles)) {
            // Log access violation in audit logs (SR-14, SR-24, T-02, T-36)
            AuditLog::log(
                'ACCESS_DENIED',
                'Route',
                $request->path(),
                $request->method(),
                ['user_role' => $user->role],
                ['required_roles' => $roles],
                "Unauthorized access attempt by {$user->name} ({$user->role}) to {$request->method()} {$request->path()}",
                $user->name,
                $user->role
            );

            return response()->json([
                'success' => false,
                'message' => "Access denied. Role '{$user->role}' is not authorized to access this resource.",
                'audit' => [
                    'actor' => $user->name,
                    'role' => $user->role,
                    'action' => 'ACCESS_DENIED',
                    'timestamp' => now()->toIso8601String(),
                ]
            ], 403);
        }

        return $next($request);
    }
}

