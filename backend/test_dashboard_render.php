<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
if (!$user) {
    die("No user found\n");
}
auth()->login($user);

try {
    $controller = app(App\Http\Controllers\Web\DashboardController::class);
    $view = $controller->index();
    $html = $view->render();
    
    echo "SUCCESS: Dashboard rendered successfully!\n";
    echo "Length of rendered HTML: " . strlen($html) . " bytes\n";
    
    // Check key strings
    $checks = [
        'Ringkasan Eksekutif',
        'Total Prospek',
        'Peluang Aktif',
        'Pesanan Penjualan',
        'Total Pelanggan',
        'Nilai Pipeline',
        'Kinerja Penjualan',
        'salesPerformanceChart',
        'Tahapan Pipeline',
        'Pesanan Penjualan Terbaru',
        'Prospek Terbaru',
        'Tindakan Tertunda & Peringatan',
        'maison-aster-logo',
        'logo-icon'
    ];
    
    foreach ($checks as $c) {
        if (strpos($html, $c) !== false) {
            echo " [PASS] Found expected string: '$c'\n";
        } else {
            echo " [WARN] Missing expected string: '$c'\n";
        }
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}

