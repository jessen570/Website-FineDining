<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (App\Models\User::all() as $u) {
    echo "ID: {$u->id} | Name: {$u->name} | Email: '{$u->email}' | Role: '{$u->role}'\n";
}

