<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\ChartOfAccount;
use App\Models\Contact;
use App\Models\CrmLead;
use App\Models\Employee;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\RestaurantTable;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\StockLevel;
use App\Models\User;
use App\Models\VendorPricelist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with the official Maison Aster baseline dataset.
     */
    public function run(): void
    {
        // 1. Employees (E-01 to E-09)
        $employees = [
            ['employee_id' => 'E-01', 'name' => 'Maya Atmadja', 'job_position' => 'General Manager', 'department' => 'Management', 'status' => 'Aktif', 'private_phone' => '08119876001', 'private_email' => 'maya.private@gmail.com', 'private_address' => 'Jl. Senopati No. 12, Jakarta Selatan'],
            ['employee_id' => 'E-02', 'name' => 'Sari Amalia', 'job_position' => 'Host', 'department' => 'Restaurant', 'status' => 'Aktif', 'private_phone' => '08119876002', 'private_email' => 'sari.amalia@gmail.com', 'private_address' => 'Jl. Wijaya I No. 4, Jakarta Selatan'],
            ['employee_id' => 'E-03', 'name' => 'Yusuf Mahendra', 'job_position' => 'Head Chef', 'department' => 'Kitchen', 'status' => 'Aktif', 'private_phone' => '08119876003', 'private_email' => 'yusuf.chef@gmail.com', 'private_address' => 'Jl. Panglima Polim No. 8, Jakarta Selatan'],
            ['employee_id' => 'E-04', 'name' => 'Raka Putra', 'job_position' => 'Server', 'department' => 'Restaurant', 'status' => 'Aktif', 'private_phone' => '08119876004', 'private_email' => 'raka.putra@gmail.com', 'private_address' => 'Jl. Gandaria Tengah No. 15, Jakarta Selatan'],
            ['employee_id' => 'E-05', 'name' => 'Dita Anggraini', 'job_position' => 'Cashier', 'department' => 'Finance', 'status' => 'Aktif', 'private_phone' => '08119876005', 'private_email' => 'dita.anggraini@gmail.com', 'private_address' => 'Jl. Melawai Raya No. 22, Jakarta Selatan'],
            ['employee_id' => 'E-06', 'name' => 'Bima Santosa', 'job_position' => 'Warehouse Officer', 'department' => 'Warehouse', 'status' => 'Aktif', 'private_phone' => '08119876006', 'private_email' => 'bima.warehouse@gmail.com', 'private_address' => 'Jl. Fatmawati No. 40, Jakarta Selatan'],
            ['employee_id' => 'E-07', 'name' => 'Ratih Prameswari', 'job_position' => 'Finance Manager', 'department' => 'Finance', 'status' => 'Aktif', 'private_phone' => '08119876007', 'private_email' => 'ratih.finance@gmail.com', 'private_address' => 'Jl. Dharmawangsa No. 7, Jakarta Selatan'],
            ['employee_id' => 'E-08', 'name' => 'Nisa Damayanti', 'job_position' => 'Purchasing Lead', 'department' => 'Procurement', 'status' => 'Aktif', 'private_phone' => '08119876008', 'private_email' => 'nisa.proc@gmail.com', 'private_address' => 'Jl. Kemang Raya No. 18, Jakarta Selatan'],
            ['employee_id' => 'E-09', 'name' => 'Eka Wardana', 'job_position' => 'Server', 'department' => 'Restaurant', 'status' => 'Tidak Aktif', 'private_phone' => '08119876009', 'private_email' => 'eka.wardana@gmail.com', 'private_address' => 'Jl. Bangka No. 3, Jakarta Selatan'],
        ];

        foreach ($employees as $emp) {
            Employee::updateOrCreate(['employee_id' => $emp['employee_id']], $emp);
        }

        // 2. Users with Specific Roles for Testing (T-01, T-02, T-36, etc.)
        $users = [
            ['name' => 'Maya Atmadja', 'email' => 'owner@maisonaster.id', 'role' => 'owner', 'employee_code' => 'E-01'],
            ['name' => 'Sari Amalia', 'email' => 'host@maisonaster.id', 'role' => 'host', 'employee_code' => 'E-02'],
            ['name' => 'Raka Putra', 'email' => 'server@maisonaster.id', 'role' => 'server', 'employee_code' => 'E-04'],
            ['name' => 'Bima Santosa', 'email' => 'warehouse@maisonaster.id', 'role' => 'warehouse', 'employee_code' => 'E-06'],
            ['name' => 'Ratih Prameswari', 'email' => 'finance@maisonaster.id', 'role' => 'finance', 'employee_code' => 'E-07'],
            ['name' => 'Nisa Damayanti', 'email' => 'procurement@maisonaster.id', 'role' => 'procurement', 'employee_code' => 'E-08'],
            ['name' => 'Dita Anggraini', 'email' => 'cashier@maisonaster.id', 'role' => 'cashier', 'employee_code' => 'E-05'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('Password123!'),
                    'role' => $u['role'],
                    'employee_code' => $u['employee_code'],
                ]
            );
        }

        // 3. Contacts - Customers (C-001 to C-006)
        $customers = [
            ['contact_id' => 'C-001', 'name' => 'Aditya Pranoto', 'contact_type' => 'Customer', 'customer_type' => 'Individu', 'city' => 'Jakarta', 'credit_limit' => 5000000, 'payment_terms' => 'Tunai / 7 hari', 'opening_ar' => 0, 'opening_ap' => 0],
            ['contact_id' => 'C-002', 'name' => 'PT Senja Kuliner', 'contact_type' => 'Customer', 'customer_type' => 'Perusahaan', 'city' => 'Jakarta', 'credit_limit' => 750000, 'payment_terms' => '14 hari', 'opening_ar' => 400000, 'opening_ap' => 0],
            ['contact_id' => 'C-003', 'name' => 'Nadia Kurnia', 'contact_type' => 'Customer', 'customer_type' => 'Individu', 'city' => 'Tangerang', 'credit_limit' => 600000, 'payment_terms' => '7 hari', 'opening_ar' => 0, 'opening_ap' => 0],
            ['contact_id' => 'C-004', 'name' => 'PT Andalan Prima', 'contact_type' => 'Customer', 'customer_type' => 'Perusahaan', 'city' => 'Jakarta', 'credit_limit' => 3000000, 'payment_terms' => '14 hari', 'opening_ar' => 0, 'opening_ap' => 0],
            ['contact_id' => 'C-005', 'name' => 'Dewi Lestari', 'contact_type' => 'Customer', 'customer_type' => 'Individu', 'city' => 'Bogor', 'credit_limit' => 1500000, 'payment_terms' => 'Tunai', 'opening_ar' => 0, 'opening_ap' => 0],
            ['contact_id' => 'C-006', 'name' => 'Bima Wicaksono', 'contact_type' => 'Customer', 'customer_type' => 'Individu', 'city' => 'Bekasi', 'credit_limit' => 1000000, 'payment_terms' => '7 hari', 'opening_ar' => 0, 'opening_ap' => 0],
        ];

        foreach ($customers as $c) {
            Contact::updateOrCreate(['contact_id' => $c['contact_id']], $c);
        }

        // 4. Contacts - Vendors (V-01 to V-04)
        $vendors = [
            ['contact_id' => 'V-01', 'name' => 'PT Dapur Pangan Utama', 'contact_type' => 'Vendor', 'customer_type' => null, 'city' => 'Jakarta', 'credit_limit' => 0, 'payment_terms' => '14 hari', 'opening_ar' => 0, 'opening_ap' => 300000],
            ['contact_id' => 'V-02', 'name' => 'PT Artisan Boga', 'contact_type' => 'Vendor', 'customer_type' => null, 'city' => 'Bogor', 'credit_limit' => 0, 'payment_terms' => '14 hari', 'opening_ar' => 0, 'opening_ap' => 0],
            ['contact_id' => 'V-03', 'name' => 'PT Minuman Premium', 'contact_type' => 'Vendor', 'customer_type' => null, 'city' => 'Tangerang', 'credit_limit' => 0, 'payment_terms' => '7 hari', 'opening_ar' => 0, 'opening_ap' => 0],
            ['contact_id' => 'V-04', 'name' => 'Koperasi Kopi Raya', 'contact_type' => 'Vendor', 'customer_type' => null, 'city' => 'Jakarta', 'credit_limit' => 0, 'payment_terms' => '7 hari', 'opening_ar' => 0, 'opening_ap' => 0],
        ];

        foreach ($vendors as $v) {
            Contact::updateOrCreate(['contact_id' => $v['contact_id']], $v);
        }

        // 5. Products (FD-001 to BV-015)
        $products = [
            ['variant_sku' => 'FD-001', 'name' => 'Wagyu Steak', 'size' => '200 g', 'color' => 'Medium', 'unit' => 'porsi', 'sales_price' => 350000, 'standard_cost' => 140000, 'default_vendor_id' => 'V-01'],
            ['variant_sku' => 'FD-002', 'name' => 'Salmon Confit', 'size' => '180 g', 'color' => 'Original', 'unit' => 'porsi', 'sales_price' => 270000, 'standard_cost' => 110000, 'default_vendor_id' => 'V-01'],
            ['variant_sku' => 'FD-003', 'name' => 'Truffle Pasta', 'size' => '250 g', 'color' => 'Cream', 'unit' => 'porsi', 'sales_price' => 195000, 'standard_cost' => 72000, 'default_vendor_id' => 'V-01'],
            ['variant_sku' => 'FD-004', 'name' => 'Mushroom Risotto', 'size' => '250 g', 'color' => 'Original', 'unit' => 'porsi', 'sales_price' => 180000, 'standard_cost' => 66000, 'default_vendor_id' => 'V-01'],
            ['variant_sku' => 'FD-005', 'name' => 'Duck Breast', 'size' => '200 g', 'color' => 'Rose', 'unit' => 'porsi', 'sales_price' => 290000, 'standard_cost' => 120000, 'default_vendor_id' => 'V-01'],
            ['variant_sku' => 'FD-006', 'name' => 'Caesar Salad', 'size' => '180 g', 'color' => 'Original', 'unit' => 'porsi', 'sales_price' => 105000, 'standard_cost' => 35000, 'default_vendor_id' => 'V-02'],
            ['variant_sku' => 'FD-007', 'name' => 'Burrata Salad', 'size' => '180 g', 'color' => 'Original', 'unit' => 'porsi', 'sales_price' => 165000, 'standard_cost' => 65000, 'default_vendor_id' => 'V-02'],
            ['variant_sku' => 'FD-008', 'name' => 'Lobster Bisque', 'size' => '200 ml', 'color' => 'Original', 'unit' => 'porsi', 'sales_price' => 155000, 'standard_cost' => 62000, 'default_vendor_id' => 'V-02'],
            ['variant_sku' => 'FD-009', 'name' => 'Chocolate Fondant', 'size' => '120 g', 'color' => 'Dark', 'unit' => 'porsi', 'sales_price' => 115000, 'standard_cost' => 40000, 'default_vendor_id' => 'V-02'],
            ['variant_sku' => 'FD-010', 'name' => 'Tiramisu', 'size' => '130 g', 'color' => 'Classic', 'unit' => 'porsi', 'sales_price' => 98000, 'standard_cost' => 32000, 'default_vendor_id' => 'V-02'],
            ['variant_sku' => 'BV-011', 'name' => 'Sparkling Water', 'size' => '330 ml', 'color' => 'Original', 'unit' => 'botol', 'sales_price' => 55000, 'standard_cost' => 18000, 'default_vendor_id' => 'V-03'],
            ['variant_sku' => 'BV-012', 'name' => 'Still Water', 'size' => '330 ml', 'color' => 'Original', 'unit' => 'botol', 'sales_price' => 45000, 'standard_cost' => 14000, 'default_vendor_id' => 'V-03'],
            ['variant_sku' => 'BV-013', 'name' => 'Citrus Mocktail', 'size' => '250 ml', 'color' => 'Lemon', 'unit' => 'gelas', 'sales_price' => 85000, 'standard_cost' => 26000, 'default_vendor_id' => 'V-03'],
            ['variant_sku' => 'BV-014', 'name' => 'Espresso', 'size' => '30 ml', 'color' => 'Double', 'unit' => 'cangkir', 'sales_price' => 45000, 'standard_cost' => 12000, 'default_vendor_id' => 'V-04'],
            ['variant_sku' => 'BV-015', 'name' => 'Earl Grey Tea', 'size' => '200 ml', 'color' => 'Hot', 'unit' => 'cangkir', 'sales_price' => 48000, 'standard_cost' => 13000, 'default_vendor_id' => 'V-04'],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(['variant_sku' => $p['variant_sku']], $p);
        }

        // 6. Vendor Pricelist
        $pricelists = [
            ['vendor_id' => 'V-02', 'variant_sku' => 'FD-006', 'unit_purchase_price' => 36000, 'currency' => 'IDR', 'min_qty' => 1],
            ['vendor_id' => 'V-01', 'variant_sku' => 'FD-003', 'unit_purchase_price' => 76000, 'currency' => 'IDR', 'min_qty' => 1],
            ['vendor_id' => 'V-03', 'variant_sku' => 'BV-011', 'unit_purchase_price' => 19000, 'currency' => 'IDR', 'min_qty' => 1],
            ['vendor_id' => 'V-04', 'variant_sku' => 'BV-014', 'unit_purchase_price' => 12500, 'currency' => 'IDR', 'min_qty' => 1],
        ];

        foreach ($pricelists as $pl) {
            VendorPricelist::updateOrCreate(
                ['vendor_id' => $pl['vendor_id'], 'variant_sku' => $pl['variant_sku']],
                $pl
            );
        }

        // 7. Locations
        $locations = [
            ['location_code' => 'KITCH-JKT', 'name' => 'Dapur utama / prepared items', 'type' => 'Internal', 'company' => 'Maison Aster'],
            ['location_code' => 'BAR-JKT', 'name' => 'Area minuman / beverage items', 'type' => 'Internal', 'company' => 'Maison Aster'],
        ];

        foreach ($locations as $loc) {
            Location::updateOrCreate(['location_code' => $loc['location_code']], $loc);
        }

        // 8. Opening Stock Levels (Total 277 units = Rp10.666.000)
        // Note: S00007 locks 2 FD-003 on KITCH-JKT (Reserved = 2, Available = 12)
        $stockKitch = [
            'FD-001' => 10,
            'FD-002' => 8,
            'FD-003' => 14, // Reserved will be set to 2
            'FD-004' => 12,
            'FD-005' => 8,
            'FD-006' => 20,
            'FD-007' => 9,
            'FD-008' => 12,
            'FD-009' => 18,
            'FD-010' => 16,
            'BV-011' => 0,
            'BV-012' => 0,
            'BV-013' => 0,
            'BV-014' => 0,
            'BV-015' => 0,
        ];

        foreach ($stockKitch as $sku => $qty) {
            StockLevel::updateOrCreate(
                ['location_code' => 'KITCH-JKT', 'variant_sku' => $sku],
                [
                    'on_hand' => $qty,
                    'reserved' => ($sku === 'FD-003') ? 2 : 0,
                ]
            );
        }

        $stockBar = [
            'FD-001' => 0,
            'FD-002' => 0,
            'FD-003' => 0,
            'FD-004' => 0,
            'FD-005' => 0,
            'FD-006' => 0,
            'FD-007' => 0,
            'FD-008' => 0,
            'FD-009' => 0,
            'FD-010' => 0,
            'BV-011' => 30,
            'BV-012' => 40,
            'BV-013' => 20,
            'BV-014' => 35,
            'BV-015' => 25,
        ];

        foreach ($stockBar as $sku => $qty) {
            StockLevel::updateOrCreate(
                ['location_code' => 'BAR-JKT', 'variant_sku' => $sku],
                [
                    'on_hand' => $qty,
                    'reserved' => 0,
                ]
            );
        }

        // 9. Restaurant Tables (Total 22 seats)
        $tables = [
            ['table_id' => 'TB-01', 'floor' => 'Main Dining', 'seats' => 2, 'status' => 'available'],
            ['table_id' => 'TB-02', 'floor' => 'Main Dining', 'seats' => 4, 'status' => 'available'],
            ['table_id' => 'TB-03', 'floor' => 'Main Dining', 'seats' => 4, 'status' => 'available'],
            ['table_id' => 'TB-04', 'floor' => 'Private Room', 'seats' => 6, 'status' => 'available'],
            ['table_id' => 'TB-05', 'floor' => 'Window', 'seats' => 2, 'status' => 'available'],
            ['table_id' => 'TB-06', 'floor' => 'Window', 'seats' => 4, 'status' => 'available'],
        ];

        foreach ($tables as $t) {
            RestaurantTable::updateOrCreate(['table_id' => $t['table_id']], $t);
        }

        // 10. Chart of Accounts
        $accounts = [
            ['account_code' => '1010', 'account_name' => 'Bank', 'type' => 'Aset'],
            ['account_code' => '1100', 'account_name' => 'Piutang Usaha', 'type' => 'Aset'],
            ['account_code' => '1200', 'account_name' => 'Persediaan', 'type' => 'Aset'],
            ['account_code' => '2010', 'account_name' => 'Utang Usaha', 'type' => 'Liabilitas'],
            ['account_code' => '2020', 'account_name' => 'Utang PBJT', 'type' => 'Liabilitas'],
            ['account_code' => '2030', 'account_name' => 'GRNI: Barang Diterima Belum Ditagih', 'type' => 'Liabilitas'],
            ['account_code' => '3010', 'account_name' => 'Modal Awal', 'type' => 'Ekuitas'],
            ['account_code' => '4010', 'account_name' => 'Penjualan Makanan/Minuman', 'type' => 'Pendapatan'],
            ['account_code' => '4020', 'account_name' => 'Retur Penjualan', 'type' => 'Kontra Pendapatan'],
            ['account_code' => '5010', 'account_name' => 'Harga Pokok Penjualan', 'type' => 'Beban'],
            ['account_code' => '5020', 'account_name' => 'Selisih Harga Pembelian', 'type' => 'Beban'],
            ['account_code' => '6010', 'account_name' => 'Biaya Operasional', 'type' => 'Beban'],
            ['account_code' => '1015', 'account_name' => 'Kas Restoran', 'type' => 'Aset'],
            ['account_code' => '2040', 'account_name' => 'Kredit Pelanggan', 'type' => 'Liabilitas'],
            ['account_code' => '2990', 'account_name' => 'Utang Lainnya', 'type' => 'Liabilitas'],
        ];

        foreach ($accounts as $acc) {
            ChartOfAccount::updateOrCreate(['account_code' => $acc['account_code']], $acc);
        }

        // 11. Opening Journal Entry (Balanced: Debit 26.066.000 = Credit 26.066.000)
        $openingEntry = JournalEntry::updateOrCreate(
            ['entry_id' => 'JE/OPENING'],
            [
                'entry_date' => '2026-11-01',
                'reference_type' => 'Opening',
                'reference_id' => 'INIT_2026',
                'description' => 'Saldo Awal Pembukuan Maison Aster',
                'status' => 'Posted',
                'total_debit' => 26066000,
                'total_credit' => 26066000,
                'is_posted' => true,
            ]
        );

        $journalItems = [
            ['account_code' => '1010', 'account_name' => 'Bank', 'description' => 'Saldo Awal Bank', 'debit' => 15000000, 'credit' => 0],
            ['account_code' => '1100', 'account_name' => 'Piutang Usaha', 'description' => 'Saldo Awal Piutang PT Senja Kuliner (C-002)', 'debit' => 400000, 'credit' => 0],
            ['account_code' => '1200', 'account_name' => 'Persediaan', 'description' => 'Saldo Awal 277 Unit Persediaan Dapur & Bar', 'debit' => 10666000, 'credit' => 0],
            ['account_code' => '2010', 'account_name' => 'Utang Usaha', 'description' => 'Saldo Awal Utang PT Dapur Pangan Utama (V-01)', 'debit' => 0, 'credit' => 300000],
            ['account_code' => '3010', 'account_name' => 'Modal Awal', 'description' => 'Modal Awal Disetor', 'debit' => 0, 'credit' => 25766000],
        ];

        JournalItem::where('entry_id', 'JE/OPENING')->delete();
        foreach ($journalItems as $item) {
            JournalItem::create(array_merge($item, ['entry_id' => 'JE/OPENING']));
        }

        // 12. Existing Documents
        // L0001: CRM Lead
        CrmLead::updateOrCreate(
            ['lead_id' => 'L0001'],
            [
                'customer_id' => 'C-004',
                'name' => 'Acara Gathering PT Andalan Prima',
                'stage' => 'Lead',
                'expected_revenue' => 1500000,
                'is_converted' => false,
                'notes' => 'Permintaan paket fine dining korporat.',
            ]
        );

        // S00007: Existing Confirmed Sales Order (Locks 2 FD-003, Unbilled Rp390.000 + PBJT Rp39.000 = Rp429.000)
        $so7 = SalesOrder::updateOrCreate(
            ['order_id' => 'S00007'],
            [
                'customer_id' => 'C-002',
                'status' => 'Confirmed',
                'payment_terms' => '14 hari',
                'order_date' => '2026-11-01',
                'discount_percent' => 0,
                'subtotal' => 390000,
                'pbjt_amount' => 39000,
                'gross_amount' => 429000,
                'is_locked' => true,
                'delivery_status' => 'Pending',
                'invoice_status' => 'To Invoice',
                'notes' => 'Existing legacy order.',
            ]
        );

        SalesOrderLine::updateOrCreate(
            ['order_id' => 'S00007', 'variant_sku' => 'FD-003'],
            [
                'ordered_qty' => 2,
                'delivered_qty' => 0,
                'invoiced_qty' => 0,
                'unit_price' => 195000,
                'discount_percent' => 0,
                'subtotal' => 390000,
                'pbjt_amount' => 39000,
                'total_amount' => 429000,
            ]
        );

        // PO00007: Existing Confirmed Purchase Order (3 FD-003 @ Rp76.000 = Rp228.000)
        PurchaseOrder::updateOrCreate(
            ['po_id' => 'PO00007'],
            [
                'vendor_id' => 'V-01',
                'status' => 'Confirmed',
                'payment_terms' => '14 hari',
                'order_date' => '2026-11-01',
                'total_amount' => 228000,
                'is_locked' => true,
                'receipt_status' => 'Pending',
                'billing_status' => 'Nothing to Bill',
                'notes' => 'Existing purchase order for Truffle Pasta raw materials.',
            ]
        );

        PurchaseOrderLine::updateOrCreate(
            ['po_id' => 'PO00007', 'variant_sku' => 'FD-003'],
            [
                'ordered_qty' => 3,
                'received_qty' => 0,
                'accepted_qty' => 0,
                'billed_qty' => 0,
                'unit_purchase_price' => 76000,
                'subtotal' => 228000,
            ]
        );

        // Audit Log for baseline seed
        AuditLog::log(
            'SEED_BASELINE',
            'System',
            'BASELINE',
            'INIT_2026',
            null,
            ['inventory_units' => 277, 'inventory_value' => 10666000, 'opening_ar' => 400000, 'opening_ap' => 300000],
            'Database baseline dataset loaded according to Maison Aster specifications'
        );
    }
}
