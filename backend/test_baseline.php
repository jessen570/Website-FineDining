<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo '=== BASELINE VERIFICATION (T-35 / S-18) ===' . PHP_EOL;
echo 'Users: ' . App\Models\User::count() . PHP_EOL;
echo 'Employees: ' . App\Models\Employee::count() . ' (EXPECT: 9)' . PHP_EOL;
echo 'Contacts (Customers): ' . App\Models\Contact::where('contact_type','Customer')->count() . ' (EXPECT: 6)' . PHP_EOL;
echo 'Contacts (Vendors): ' . App\Models\Contact::where('contact_type','Vendor')->count() . ' (EXPECT: 4)' . PHP_EOL;
echo 'Products: ' . App\Models\Product::count() . ' (EXPECT: 15)' . PHP_EOL;

// T-35 stock baseline
$totalStock  = App\Models\StockLevel::sum('on_hand');
$reserved    = App\Models\StockLevel::sum('reserved');
$available   = $totalStock - $reserved;
echo "Total Stock (on_hand): {$totalStock} (EXPECT: 277) => " . ($totalStock == 277 ? 'PASS' : 'FAIL') . PHP_EOL;
echo "Reserved: {$reserved} (EXPECT: 2) => " . ($reserved == 2 ? 'PASS' : 'FAIL') . PHP_EOL;
echo "Available: {$available} (EXPECT: 275) => " . ($available == 275 ? 'PASS' : 'FAIL') . PHP_EOL;

// Compute stock value
$stockLevels = App\Models\StockLevel::with('product')->get();
$stockValue = $stockLevels->sum(function($sl) {
    $cost = $sl->product ? (float)$sl->product->standard_cost : 0;
    return (float)$sl->on_hand * $cost;
});
echo "Stock Value: {$stockValue} (EXPECT: 10666000) => " . ($stockValue == 10666000 ? 'PASS' : 'FAIL') . PHP_EOL;

// T-05 FD-003 KITCH
$fd003 = App\Models\StockLevel::where('location_code','KITCH-JKT')->where('variant_sku','FD-003')->first();
$fd003Avail = $fd003 ? ($fd003->on_hand - $fd003->reserved) : -1;
echo "FD-003 KITCH on_hand: " . ($fd003 ? $fd003->on_hand : 'NULL') . " (EXPECT: 14) => " . ($fd003 && $fd003->on_hand == 14 ? 'PASS' : 'FAIL') . PHP_EOL;
echo "FD-003 KITCH reserved: " . ($fd003 ? $fd003->reserved : 'NULL') . " (EXPECT: 2) => " . ($fd003 && $fd003->reserved == 2 ? 'PASS' : 'FAIL') . PHP_EOL;
echo "FD-003 KITCH available: {$fd003Avail} (EXPECT: 12) => " . ($fd003Avail == 12 ? 'PASS' : 'FAIL') . PHP_EOL;

// S00007
$so7 = App\Models\SalesOrder::where('order_id','S00007')->first();
echo "S00007 status: " . ($so7 ? $so7->status : 'NOT FOUND') . " (EXPECT: Confirmed) => " . ($so7 && $so7->status == 'Confirmed' ? 'PASS' : 'FAIL') . PHP_EOL;
echo "S00007 gross_amount: " . ($so7 ? $so7->gross_amount : 'NULL') . " (EXPECT: 429000) => " . ($so7 && $so7->gross_amount == 429000 ? 'PASS' : 'FAIL') . PHP_EOL;

// PO00007
$po7 = App\Models\PurchaseOrder::where('po_id','PO00007')->first();
echo "PO00007 exists: " . ($po7 ? 'YES' : 'NO') . " => " . ($po7 ? 'PASS' : 'FAIL') . PHP_EOL;

// L0001
$l1 = App\Models\CrmLead::where('lead_id','L0001')->first();
echo "L0001 stage: " . ($l1 ? $l1->stage : 'NOT FOUND') . " (EXPECT: Lead) => " . ($l1 && $l1->stage == 'Lead' ? 'PASS' : 'FAIL') . PHP_EOL;
echo "L0001 expected_revenue: " . ($l1 ? $l1->expected_revenue : 'NULL') . " (EXPECT: 1500000) => " . ($l1 && $l1->expected_revenue == 1500000 ? 'PASS' : 'FAIL') . PHP_EOL;

// Opening Journal Entry
$je = App\Models\JournalEntry::where('entry_id','JE/OPENING')->first();
echo "Opening JE total_debit: " . ($je ? $je->total_debit : 'NOT FOUND') . " (EXPECT: 26066000) => " . ($je && $je->total_debit == 26066000 ? 'PASS' : 'FAIL') . PHP_EOL;
echo "Opening JE total_credit: " . ($je ? $je->total_credit : 'NOT FOUND') . " (EXPECT: 26066000) => " . ($je && $je->total_credit == 26066000 ? 'PASS' : 'FAIL') . PHP_EOL;
$jeItems = App\Models\JournalItem::where('entry_id','JE/OPENING')->count();
echo "Opening JE items: {$jeItems} (EXPECT: 5) => " . ($jeItems == 5 ? 'PASS' : 'FAIL') . PHP_EOL;

// Chart of Accounts
$coaCount = App\Models\ChartOfAccount::count();
echo "Chart of Accounts: {$coaCount} (EXPECT: 15) => " . ($coaCount == 15 ? 'PASS' : 'FAIL') . PHP_EOL;

// Restaurant Tables
$tableCount = App\Models\RestaurantTable::count();
$tableSeats = App\Models\RestaurantTable::sum('seats');
echo "Restaurant Tables: {$tableCount} (EXPECT: 6) => " . ($tableCount == 6 ? 'PASS' : 'FAIL') . PHP_EOL;
echo "Total Seats: {$tableSeats} (EXPECT: 22) => " . ($tableSeats == 22 ? 'PASS' : 'FAIL') . PHP_EOL;

// BAR-JKT BV-011
$bv011Bar = App\Models\StockLevel::where('location_code','BAR-JKT')->where('variant_sku','BV-011')->first();
echo "BV-011 BAR on_hand: " . ($bv011Bar ? $bv011Bar->on_hand : 'NULL') . " (EXPECT: 30) => " . ($bv011Bar && $bv011Bar->on_hand == 30 ? 'PASS' : 'FAIL') . PHP_EOL;

// User roles
$roles = App\Models\User::select('role')->distinct()->pluck('role')->implode(', ');
echo "User roles seeded: {$roles}" . PHP_EOL;

// Inactive employee
$eka = App\Models\Employee::where('employee_id','E-09')->first();
echo "E-09 Eka Wardana status: " . ($eka ? $eka->status : 'NOT FOUND') . " (EXPECT: Tidak Aktif) => " . ($eka && $eka->status == 'Tidak Aktif' ? 'PASS' : 'FAIL') . PHP_EOL;

echo PHP_EOL . '=== SR-15: C-002 Credit Exposure ===' . PHP_EOL;
$c002 = App\Models\Contact::where('contact_id','C-002')->first();
echo "C-002 credit_limit: " . ($c002 ? $c002->credit_limit : 'NULL') . " (EXPECT: 750000)" . PHP_EOL;
echo "C-002 opening_ar: " . ($c002 ? $c002->opening_ar : 'NULL') . " (EXPECT: 400000)" . PHP_EOL;
echo "S00007 gross (unbilled): " . ($so7 ? $so7->gross_amount : 'NULL') . " (EXPECT: 429000)" . PHP_EOL;
echo "Expected total exposure: " . (400000 + 429000) . " vs limit 750000 => " . ((400000 + 429000) > 750000 ? 'OVER LIMIT (expected)' : 'UNDER LIMIT') . PHP_EOL;
