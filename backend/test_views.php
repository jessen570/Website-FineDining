<?php
// Verify all 9 views have been built (no longer placeholder)
$files = [
    'invoices/index.blade.php',
    'invoices/pay.blade.php',
    'invoices/returns_index.blade.php',
    'invoices/return_create.blade.php',
    'invoices/return_show.blade.php',
    'accounting/index.blade.php',
    'accounting/entries.blade.php',
    'accounting/ledger.blade.php',
    'accounting/entry_show.blade.php',
];

$pass = 0; $fail = 0;

foreach ($files as $f) {
    $path = 'resources/views/' . $f;
    if (!file_exists($path)) {
        echo "[FAIL] FILE NOT FOUND: {$f}" . PHP_EOL;
        $fail++;
        continue;
    }
    $content = file_get_contents($path);
    $isPlaceholder = strpos($content, "@extends('placeholder')") !== false;
    $hasExtends    = strpos($content, "@extends('layouts.app')") !== false;
    $hasTitle      = strpos($content, "@section('title'") !== false;
    $hasContent    = strpos($content, "@section('content')") !== false;
    $hasBreadcrumb = strpos($content, "@section('breadcrumb')") !== false;
    $lines         = count(explode("\n", $content));

    // Check for key Odoo-style elements
    $hasTable      = strpos($content, 'data-table') !== false;
    $hasBtn        = strpos($content, 'class="btn') !== false;
    $hasBadge      = strpos($content, 'class="badge') !== false;
    $hasSR         = preg_match('/SR-\d+/', $content);

    $status = (!$isPlaceholder && $hasExtends && $hasTitle && $hasContent) ? 'PASS' : 'FAIL';
    if ($status === 'PASS') $pass++; else $fail++;

    echo "[{$status}] {$f}" . PHP_EOL;
    echo "       Lines:{$lines} | Extends:{$hasExtends} | Placeholder:{$isPlaceholder} | Title:{$hasTitle} | Content:{$hasContent}" . PHP_EOL;
    echo "       Table:{$hasTable} | Buttons:{$hasBtn} | Badges:{$hasBadge} | SR-rules:{$hasSR}" . PHP_EOL;
}

echo PHP_EOL . "=== RESULT: {$pass} PASS, {$fail} FAIL ===" . PHP_EOL;

// Check still-remaining placeholders
echo PHP_EOL . "=== REMAINING PLACEHOLDER FILES ===" . PHP_EOL;
$allViews = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('resources/views'));
$remaining = 0;
foreach ($allViews as $file) {
    if ($file->getExtension() === 'php') {
        $c = file_get_contents($file->getPathname());
        if (strpos($c, "@extends('placeholder')") !== false) {
            $rel = str_replace('resources/views/', '', $file->getPathname());
            echo "  [PLACEHOLDER] " . $rel . PHP_EOL;
            $remaining++;
        }
    }
}
if ($remaining === 0) echo "  None! All views are real pages." . PHP_EOL;
else echo PHP_EOL . "  Total remaining: {$remaining} placeholder(s)" . PHP_EOL;
