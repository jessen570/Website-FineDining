<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "===============================================================\n";
echo "MAISON ASTER ERP — AUTHENTICATION TEST SUITE\n";
echo "===============================================================\n\n";

$authController = new \App\Http\Controllers\Web\AuthController();

// 1. Test GET Login View
echo ">>> 1. Testing GET /login view rendering\n";
$view = $authController->showLogin();
$view->with('errors', new \Illuminate\Support\ViewErrorBag());
$html = $view->render();

$hasBrand = str_contains($html, 'MAISON ASTER') && str_contains($html, 'FINE DINING ERP');
$hasForm = str_contains($html, 'Masuk ke Akun') && str_contains($html, 'nama@maisonaster.id');
$hasPasswordToggle = str_contains($html, 'togglePasswordVisibility()');
$hasRemember = str_contains($html, 'Ingat saya') && str_contains($html, 'name="remember"');
$hasForgotPassword = str_contains($html, 'Lupa password?');
$hasNoDemo = !str_contains($html, 'Akun Demo') && !str_contains($html, 'Password123!');

echo "- Brand Identity rendered: " . ($hasBrand ? "OK" : "MISSING") . "\n";
echo "- Login Form fields rendered: " . ($hasForm ? "OK" : "MISSING") . "\n";
echo "- Password toggle present: " . ($hasPasswordToggle ? "OK" : "MISSING") . "\n";
echo "- Remember & Forgot Password: " . ($hasRemember && $hasForgotPassword ? "OK" : "MISSING") . "\n";
echo "- Demo accounts removed: " . ($hasNoDemo ? "OK" : "FAILED (Demo accounts still found)") . "\n";

$viewPass = $hasBrand && $hasForm && $hasPasswordToggle && $hasRemember && $hasForgotPassword && $hasNoDemo;
echo "Result View Test: " . ($viewPass ? "PASS" : "FAIL") . "\n\n";

// 2. Test Invalid Login Attempt
echo ">>> 2. Testing POST /login with invalid credentials\n";
$invalidRequest = Request::create('/login', 'POST', [
    'email' => 'wrong@maisonaster.id',
    'password' => 'WrongPassword!',
]);
// Session simulation
$session = $app->make('session')->driver();
$invalidRequest->setLaravelSession($session);

$invalidResponse = $authController->login($invalidRequest);
$isRedirectBack = $invalidResponse->isRedirection();
$hasErrors = session()->has('errors');
echo "- Redirect back on failure: " . ($isRedirectBack ? "OK" : "FAILED") . "\n";
echo "- Error bag populated: " . ($hasErrors ? "OK" : "FAILED") . "\n";
$invalidPass = $isRedirectBack && $hasErrors;
echo "Result Invalid Login Test: " . ($invalidPass ? "PASS" : "FAIL") . "\n\n";

// 3. Test Valid Login Attempt
echo ">>> 3. Testing POST /login with valid credentials (owner@maisonaster.id)\n";
$validRequest = Request::create('/login', 'POST', [
    'email' => 'owner@maisonaster.id',
    'password' => 'Password123!',
    'remember' => 'on',
]);
$validRequest->setLaravelSession($session);

$validResponse = $authController->login($validRequest);
$isAuth = Auth::check();
$currentUser = Auth::user();
$auditLogin = AuditLog::where('action', 'LOGIN')->where('actor_name', $currentUser?->name)->latest('created_at')->first();

echo "- Authenticated: " . ($isAuth ? "YES (User: {$currentUser->name}, Role: {$currentUser->role})" : "NO") . "\n";
echo "- Redirect destination: " . $validResponse->getTargetUrl() . "\n";
echo "- Audit log written: " . ($auditLogin ? "YES ('{$auditLogin->message}')" : "NO") . "\n";

$validPass = $isAuth && ($currentUser->email === 'owner@maisonaster.id') && ($auditLogin !== null);
echo "Result Valid Login Test: " . ($validPass ? "PASS" : "FAIL") . "\n\n";

// 4. Test Logout
echo ">>> 4. Testing POST /logout\n";
$logoutRequest = Request::create('/logout', 'POST');
$logoutRequest->setLaravelSession($session);
$logoutResponse = $authController->logout($logoutRequest);

$isLoggedOut = !Auth::check();
$auditLogout = AuditLog::where('action', 'LOGOUT')->latest('created_at')->first();

echo "- Logged out: " . ($isLoggedOut ? "YES" : "NO") . "\n";
echo "- Audit log written: " . ($auditLogout ? "YES ('{$auditLogout->message}')" : "NO") . "\n";

$logoutPass = $isLoggedOut && ($auditLogout !== null);
echo "Result Logout Test: " . ($logoutPass ? "PASS" : "FAIL") . "\n\n";

$allAuthPass = $viewPass && $invalidPass && $validPass && $logoutPass;
echo "===============================================================\n";
echo "OVERALL AUTHENTICATION STATUS: " . ($allAuthPass ? "ALL TESTS PASSED (4/4)" : "SOME TESTS FAILED") . "\n";
echo "===============================================================\n";
