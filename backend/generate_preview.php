<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
auth()->login($user);

$controller = app(App\Http\Controllers\Web\DashboardController::class);
$view = $controller->index();
$html = $view->render();

// Replace relative assets to absolute or localhost urls for standalone preview
$html = str_replace('href="http://localhost/images/', 'href="http://localhost:8000/images/', $html);
$html = str_replace('src="http://localhost/images/', 'src="http://localhost:8000/images/', $html);

file_put_contents(__DIR__ . '/public/dashboard_preview.html', $html);
echo "Preview generated at backend/public/dashboard_preview.html\n";

