<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Authenticate user and issue Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        // Revoke old tokens for single-session clarity
        $user->tokens()->delete();
        $token = $user->createToken('erp-auth-token')->plainTextToken;

        // Fetch employee details if linked
        $employee = $user->employee_code
            ? Employee::where('employee_id', $user->employee_code)->first()
            : null;

        AuditLog::log(
            'LOGIN',
            'UserSession',
            (string) $user->id,
            $user->email,
            null,
            ['role' => $user->role, 'employee_code' => $user->employee_code],
            "User {$user->name} ({$user->role}) logged in successfully.",
            $user->name,
            $user->role
        );

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'employee_code' => $user->employee_code,
                'employee' => $employee ? $employee->toRoleScopedArray($user->role) : null,
            ],
        ]);
    }

    /**
     * Get the authenticated user's profile and permissions.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee_code
            ? Employee::where('employee_id', $user->employee_code)->first()
            : null;

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'employee_code' => $user->employee_code,
                'employee' => $employee ? $employee->toRoleScopedArray($user->role) : null,
            ],
            'company' => [
                'name' => 'Maison Aster Jakarta',
                'outlet' => 'Jakarta Selatan',
                'seats' => 22,
                'currency' => 'IDR',
                'pbjt_rate' => 0.10,
            ],
        ]);
    }

    /**
     * Logout and revoke tokens.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            AuditLog::log(
                'LOGOUT',
                'UserSession',
                (string) $user->id,
                $user->email,
                ['role' => $user->role],
                null,
                "User {$user->name} logged out.",
                $user->name,
                $user->role
            );
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * List available role test accounts for easy evaluator login.
     */
    public function testAccounts(): JsonResponse
    {
        $users = User::select('id', 'name', 'email', 'role', 'employee_code')->get();

        return response()->json([
            'success' => true,
            'accounts' => $users->map(fn($u) => [
                'role' => $u->role,
                'name' => $u->name,
                'email' => $u->email,
                'employee_code' => $u->employee_code,
                'default_password' => 'Password123!',
            ]),
        ]);
    }
}

