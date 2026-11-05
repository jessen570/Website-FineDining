<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $input = trim((string) $request->input('email', ''));

        // Allow login by email or role/username (e.g. 'owner' -> 'owner@maisonaster.id')
        $targetUser = \App\Models\User::where('email', $input)
            ->orWhere('email', $input . '@maisonaster.id')
            ->orWhere('role', strtolower($input))
            ->first();

        $email = $targetUser ? $targetUser->email : (str_contains($input, '@') ? $input : $input . '@maisonaster.id');

        $credentials = [
            'email'    => $email,
            'password' => (string) $request->password,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            AuditLog::log(
                'LOGIN',
                'UserSession',
                (string) $user->id,
                $user->email,
                null,
                ['role' => $user->role],
                "User {$user->name} ({$user->role}) logged in.",
                $user->name,
                $user->role
            );

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

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
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
