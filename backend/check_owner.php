<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$u = App\Models\User::where('email', 'owner@maisonaster.id')->first();
if ($u) {
    echo "Found user: {$u->name} ({$u->email})\n";
    echo "Password check 'Password123!': " . (Illuminate\Support\Facades\Hash::check('Password123!', $u->password) ? 'MATCH' : 'MISMATCH') . "\n";
    echo "Password check 'password': " . (Illuminate\Support\Facades\Hash::check('password', $u->password) ? 'MATCH' : 'MISMATCH') . "\n";
} else {
    echo "Owner user NOT FOUND!\n";
}

