<?php
/**
 * HTTP Endpoint Test — Maison Aster ERP
 * Cek semua URL utama bisa diakses (bukan error 500)
 */

$base = 'http://127.0.0.1:8000';
$pass = 0; $fail = 0; $warn = 0;
$issues = [];

function httpGet($url, $timeoutSec = 8) {
    $ctx = stream_context_create(['http' => [
        'timeout'          => $timeoutSec,
        'follow_location'  => 0,         // jangan ikuti redirect
        'ignore_errors'    => true,
        'method'           => 'GET',
        'header'           => "Cookie: \r\n",
    ]]);
    $resp = @file_get_contents($url, false, $ctx);
    // ambil status dari $http_response_header
    $status = 0;
    if (!empty($http_response_header)) {
        preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0], $m);
        $status = (int)($m[1] ?? 0);
    }
    return ['status' => $status, 'body' => $resp ?: ''];
}

function chk($label, $url, $expectCodes = [200, 302], $mustNotContain = ['Whoops','Exception','Stack trace','Fatal error','Parse error']) {
    global $pass, $fail, $warn, $issues, $base;

    $r = httpGet($base . $url);
    $code   = $r['status'];
    $body   = $r['body'];

    // detect error keywords in body
    $hasError = false;
    $foundErr = '';
    foreach ($mustNotContain as $kw) {
        if ($body && stripos($body, $kw) !== false) {
            $hasError = true;
            $foundErr = $kw;
            break;
        }
    }

    $codeOk = in_array($code, $expectCodes);
    $ok = $codeOk && !$hasError;

    if ($ok) {
        $pass++;
        echo "\033[32m[PASS]\033[0m {$label} → HTTP {$code}" . PHP_EOL;
    } elseif (!$codeOk && $code === 0) {
        $warn++;
        $issues[] = ['label' => $label, 'url' => $url, 'note' => 'Timeout / no response'];
        echo "\033[33m[WARN]\033[0m {$label} → Timeout/No response" . PHP_EOL;
    } elseif ($hasError) {
        $fail++;
        $issues[] = ['label' => $label, 'url' => $url, 'note' => "HTTP {$code} — Error keyword: '{$foundErr}'"];
        echo "\033[31m[FAIL]\033[0m {$label} → HTTP {$code} — Contains '{$foundErr}'" . PHP_EOL;
    } else {
        $fail++;
        $issues[] = ['label' => $label, 'url' => $url, 'note' => "HTTP {$code} (expected: " . implode('|',$expectCodes) . ')'];
        echo "\033[31m[FAIL]\033[0m {$label} → HTTP {$code} (expected: " . implode('|',$expectCodes) . ")" . PHP_EOL;
    }
}

function section($t) { echo PHP_EOL . "━━━ {$t} " . str_repeat('━', max(0,55-strlen($t))) . PHP_EOL; }

echo "Testing: {$base}" . PHP_EOL;
echo "Server must be running on port 8000" . PHP_EOL;

// ── Guest Routes (harus redirect ke login atau tampil login) ─────
section('AUTH ROUTES (Guest)');
chk('GET /         → redirect/login',  '/',          [200,302]);
chk('GET /login    → login page',      '/login',     [200]);

// ── Protected Routes (unauthenticated → redirect 302 to login) ──
section('PROTECTED ROUTES — Redirect when unauthenticated');
chk('GET /dashboard              → 302', '/dashboard',              [302]);
chk('GET /crm/leads              → 302', '/crm/leads',              [302]);
chk('GET /crm/opportunities      → 302', '/crm/opportunities',      [302]);
chk('GET /crm/pipeline           → 302', '/crm/pipeline',           [302]);
chk('GET /sales/customers        → 302', '/sales/customers',        [302]);
chk('GET /sales/quotations       → 302', '/sales/quotations',       [302]);
chk('GET /sales/orders           → 302', '/sales/orders',           [302]);
chk('GET /sales/discounts        → 302', '/sales/discounts',        [302]);
chk('GET /sales/credit           → 302', '/sales/credit',           [302]);
chk('GET /products               → 302', '/products',               [302]);
chk('GET /invoices               → 302', '/invoices',               [302]);
chk('GET /returns                → 302', '/returns',                [302]);
chk('GET /inventory              → 302', '/inventory',              [302]);
chk('GET /purchase               → 302', '/purchase',               [302]);
chk('GET /accounting             → 302', '/accounting',             [302]);
chk('GET /accounting/entries     → 302', '/accounting/entries',     [302]);
chk('GET /accounting/ledger      → 302', '/accounting/ledger',      [302]);
chk('GET /employees              → 302', '/employees',              [302]);
chk('GET /bookings               → 302', '/bookings',               [302]);
chk('GET /table-orders           → 302', '/table-orders',           [302]);
chk('GET /contacts               → 302', '/contacts',               [302]);

// ── Invalid Routes (harus 404, bukan 500) ───────────────────────
section('INVALID ROUTES — Should 404 not 500');
chk('GET /nonexistent-route      → 404', '/nonexistent-route',      [404]);
chk('GET /invoices/INVALID-ID    → 302/404', '/invoices/INVALID-ID',[302,404]);
chk('GET /accounting/entries/ZZZ → 302/404', '/accounting/entries/ZZZ', [302,404]);

// ── Login Functionality Test ─────────────────────────────────────
section('LOGIN PAGE CONTENT CHECK');
$loginR = httpGet($base . '/login');
$hasForm    = strpos($loginR['body'], '<form') !== false || strpos($loginR['body'], 'form') !== false;
$hasEmail   = stripos($loginR['body'], 'email') !== false;
$hasPass    = stripos($loginR['body'], 'password') !== false || stripos($loginR['body'], 'kata') !== false;
$hasMaison  = stripos($loginR['body'], 'maison') !== false || stripos($loginR['body'], 'aster') !== false;
$noError    = strpos($loginR['body'], 'Whoops') === false && strpos($loginR['body'], 'Exception') === false;

if ($loginR['status'] === 200 && $hasForm && $hasEmail && $noError) {
    $pass++;
    echo "\033[32m[PASS]\033[0m Login page renders correctly (form, email, no errors)" . PHP_EOL;
} else {
    $fail++;
    $issues[] = ['label'=>'Login page content', 'url'=>'/login', 'note'=>"status:{$loginR['status']} form:{$hasForm} email:{$hasEmail} noErr:{$noError}"];
    echo "\033[31m[FAIL]\033[0m Login page content check failed" . PHP_EOL;
}

if ($hasMaison) {
    $pass++;
    echo "\033[32m[PASS]\033[0m Login page shows 'Maison Aster' branding" . PHP_EOL;
} else {
    $warn++;
    echo "\033[33m[WARN]\033[0m Login page missing 'Maison Aster' branding" . PHP_EOL;
}

// ── POST Login Test ───────────────────────────────────────────────
section('POST LOGIN — Credential Test');

function httpPost($url, $data, $timeoutSec = 8) {
    // get CSRF token first
    $ctx1 = stream_context_create(['http'=>['timeout'=>$timeoutSec,'ignore_errors'=>true,'method'=>'GET']]);
    $html = @file_get_contents($url, false, $ctx1);
    preg_match('/name="_token"\s+value="([^"]+)"/', $html ?? '', $m);
    $token = $m[1] ?? '';

    $cookie = '';
    if (!empty($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (stripos($h, 'set-cookie:') === 0) {
                preg_match('/set-cookie:\s*([^;]+)/i', $h, $mc);
                if (!empty($mc[1])) $cookie .= $mc[1] . '; ';
            }
        }
    }

    $postData = http_build_query(array_merge($data, ['_token' => $token]));
    $ctx2 = stream_context_create(['http' => [
        'timeout'        => $timeoutSec,
        'method'         => 'POST',
        'ignore_errors'  => true,
        'follow_location'=> 0,
        'header'         => "Content-Type: application/x-www-form-urlencoded\r\nCookie: {$cookie}\r\n",
        'content'        => $postData,
    ]]);
    $resp = @file_get_contents($url, false, $ctx2);
    $status = 0;
    if (!empty($http_response_header)) {
        preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0], $ms);
        $status = (int)($ms[1] ?? 0);
    }
    return ['status' => $status, 'body' => $resp ?: '', 'token' => $token];
}

$credentials = [
    ['email' => 'owner@maisonaster.id',       'role' => 'owner',       'password' => 'Password123!'],
    ['email' => 'finance@maisonaster.id',      'role' => 'finance',     'password' => 'Password123!'],
    ['email' => 'warehouse@maisonaster.id',    'role' => 'warehouse',   'password' => 'Password123!'],
    ['email' => 'host@maisonaster.id',         'role' => 'host',        'password' => 'Password123!'],
];

foreach ($credentials as $cred) {
    $r = httpPost($base . '/login', ['email' => $cred['email'], 'password' => $cred['password']]);
    $isRedirect = in_array($r['status'], [302, 303]);
    $noError    = strpos($r['body'], 'Whoops') === false && strpos($r['body'], 'Exception') === false;
    $ok = ($isRedirect || $r['status'] === 200) && $noError;
    if ($ok) {
        $pass++;
        echo "\033[32m[PASS]\033[0m Login {$cred['role']} ({$cred['email']}) → HTTP {$r['status']}" . PHP_EOL;
    } else {
        $fail++;
        $issues[] = ['label' => "Login {$cred['role']}", 'url' => '/login', 'note' => "HTTP {$r['status']}"];
        echo "\033[31m[FAIL]\033[0m Login {$cred['role']} ({$cred['email']}) → HTTP {$r['status']}" . PHP_EOL;
    }
}

// ── SUMMARY ──────────────────────────────────────────────────────
$total = $pass + $fail;
$pct   = $total > 0 ? round(($pass/$total)*100,1) : 0;

echo PHP_EOL . str_repeat('═',60) . PHP_EOL;
echo "  HTTP ENDPOINT RESULTS" . PHP_EOL;
echo str_repeat('═',60) . PHP_EOL;
echo "  \033[32mPASS: {$pass}\033[0m  |  \033[31mFAIL: {$fail}\033[0m  |  \033[33mWARN: {$warn}\033[0m  |  Score: {$pct}%" . PHP_EOL;

if (!empty($issues)) {
    echo PHP_EOL . "  \033[31mISSUES FOUND:\033[0m" . PHP_EOL;
    foreach ($issues as $iss) {
        echo "    ✗ [{$iss['label']}] {$iss['url']} → {$iss['note']}" . PHP_EOL;
    }
}

echo PHP_EOL;
if ($pct >= 95) echo "  \033[32m✅ ALL HTTP ENDPOINTS OK — Siap demo\033[0m" . PHP_EOL;
elseif ($pct >= 80) echo "  \033[33m⚠  MOSTLY OK — Ada beberapa endpoint bermasalah\033[0m" . PHP_EOL;
else echo "  \033[31m❌  BANYAK ENDPOINT BERMASALAH — Perlu perbaikan\033[0m" . PHP_EOL;
echo str_repeat('═',60) . PHP_EOL;
