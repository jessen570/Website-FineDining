<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\Crm\LeadController;
use App\Http\Controllers\Web\Crm\OpportunityController;
use App\Http\Controllers\Web\Crm\PipelineController;
use App\Http\Controllers\Web\Sales\CustomerController;
use App\Http\Controllers\Web\Sales\QuotationController;
use App\Http\Controllers\Web\Sales\SalesOrderController;
use App\Http\Controllers\Web\Sales\DiscountApprovalController;
use App\Http\Controllers\Web\Sales\CreditControlController;
use App\Http\Controllers\Web\Sales\ReportingController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\InvoiceController;
use App\Http\Controllers\Web\InventoryController;
use App\Http\Controllers\Web\PurchaseController;
use App\Http\Controllers\Web\AccountingController;
use App\Http\Controllers\Web\EmployeeController;
use App\Http\Controllers\Web\BookingController;
use App\Http\Controllers\Web\TableOrderController;
use App\Http\Controllers\Web\ContactController;
use Illuminate\Support\Facades\Route;

/*
|==========================================================================
| GUEST ROUTES
|==========================================================================
*/
Route::middleware('guest.web')->group(function () {
    Route::get('/',       [AuthController::class, 'showLogin'])->name('login');
    Route::get('/login',  [AuthController::class, 'showLogin']);
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

/*
|==========================================================================
| AUTHENTICATED ROUTES
|==========================================================================
*/
Route::middleware('auth.web')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ── Dashboard — semua role boleh akses ────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ══════════════════════════════════════════════════════════════════════
    // CRM & PROSPEK — owner, host, finance
    // (owner bypass otomatis via CheckRole middleware)
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('role:host,finance')->group(function () {

        Route::prefix('crm/leads')->name('crm.leads.')->group(function () {
            Route::get('/',              [LeadController::class, 'index'])->name('index');
            Route::get('/create',        [LeadController::class, 'create'])->name('create');
            Route::post('/',             [LeadController::class, 'store'])->name('store');
            Route::get('/{id}',          [LeadController::class, 'show'])->name('show');
            Route::get('/{id}/edit',     [LeadController::class, 'edit'])->name('edit');
            Route::patch('/{id}',        [LeadController::class, 'update'])->name('update');
            Route::delete('/{id}',       [LeadController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/convert', [LeadController::class, 'convert'])->name('convert');
        });

        Route::prefix('crm/opportunities')->name('crm.opportunities.')->group(function () {
            Route::get('/',             [OpportunityController::class, 'index'])->name('index');
            Route::get('/create',       [OpportunityController::class, 'create'])->name('create');
            Route::post('/',            [OpportunityController::class, 'store'])->name('store');
            Route::get('/{id}',         [OpportunityController::class, 'show'])->name('show');
            Route::get('/{id}/edit',    [OpportunityController::class, 'edit'])->name('edit');
            Route::patch('/{id}',       [OpportunityController::class, 'update'])->name('update');
            Route::patch('/{id}/stage', [OpportunityController::class, 'updateStage'])->name('stage');
            Route::post('/{id}/won',    [OpportunityController::class, 'markWon'])->name('won');
            Route::post('/{id}/lost',   [OpportunityController::class, 'markLost'])->name('lost');
        });

        Route::get('/crm/pipeline',              [PipelineController::class, 'index'])->name('crm.pipeline');
        Route::patch('/crm/pipeline/{id}/stage', [PipelineController::class, 'updateStage'])->name('crm.pipeline.stage');

        // Backward-compat CRM aliases
        Route::get('/crm',               fn() => redirect()->route('crm.leads.index'))->name('crm.index');
        Route::get('/crm/create',        fn() => redirect()->route('crm.leads.create'))->name('crm.create');
        Route::post('/crm',              [LeadController::class, 'store'])->name('crm.store');
        Route::get('/crm/{id}',          fn($id) => redirect()->route('crm.leads.show', $id))->name('crm.show');
        Route::get('/crm/{id}/edit',     fn($id) => redirect()->route('crm.leads.edit', $id))->name('crm.edit');
        Route::patch('/crm/{id}',        [LeadController::class, 'update'])->name('crm.update');
        Route::patch('/crm/{id}/stage',  [OpportunityController::class, 'updateStage'])->name('crm.stage');
        Route::post('/crm/{id}/convert', [LeadController::class, 'convert'])->name('crm.convert');
    });

    // ══════════════════════════════════════════════════════════════════════
    // PELANGGAN — owner, finance, host, cashier
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('role:finance,host,cashier')->group(function () {
        Route::prefix('sales/customers')->name('sales.customers.')->group(function () {
            Route::get('/',            [CustomerController::class, 'index'])->name('index');
            Route::get('/create',      [CustomerController::class, 'create'])->name('create');
            Route::post('/',           [CustomerController::class, 'store'])->name('store');
            Route::get('/{id}',        [CustomerController::class, 'show'])->name('show');
            Route::get('/{id}/edit',   [CustomerController::class, 'edit'])->name('edit');
            Route::patch('/{id}',      [CustomerController::class, 'update'])->name('update');
            Route::get('/{id}/credit', [CustomerController::class, 'creditDetail'])->name('credit');
        });
    });

    // ══════════════════════════════════════════════════════════════════════
    // PENJUALAN (B2B / BANQUET) — owner, finance, host
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('role:finance,host')->group(function () {
        Route::prefix('sales/quotations')->name('sales.quotations.')->group(function () {
            Route::get('/',               [QuotationController::class, 'index'])->name('index');
            Route::get('/create',         [QuotationController::class, 'create'])->name('create');
            Route::post('/',              [QuotationController::class, 'store'])->name('store');
            Route::get('/{id}',           [QuotationController::class, 'show'])->name('show');
            Route::get('/{id}/edit',      [QuotationController::class, 'edit'])->name('edit');
            Route::patch('/{id}',         [QuotationController::class, 'update'])->name('update');
            Route::post('/{id}/send',     [QuotationController::class, 'send'])->name('send');
            Route::post('/{id}/confirm',  [QuotationController::class, 'confirm'])->name('confirm');
            Route::post('/{id}/cancel',   [QuotationController::class, 'cancel'])->name('cancel');
            Route::get('/{id}/print',     [QuotationController::class, 'print'])->name('print');
            Route::post('/{id}/to-order', [QuotationController::class, 'convertToOrder'])->name('to-order');
        });

        Route::prefix('sales/orders')->name('sales.orders.')->group(function () {
            Route::get('/',              [SalesOrderController::class, 'index'])->name('index');
            Route::get('/create',        [SalesOrderController::class, 'create'])->name('create');
            Route::post('/',             [SalesOrderController::class, 'store'])->name('store');
            Route::get('/{id}',          [SalesOrderController::class, 'show'])->name('show');
            Route::get('/{id}/edit',     [SalesOrderController::class, 'edit'])->name('edit');
            Route::patch('/{id}',        [SalesOrderController::class, 'update'])->name('update');
            Route::post('/{id}/confirm', [SalesOrderController::class, 'confirm'])->name('confirm');
            Route::post('/{id}/cancel',  [SalesOrderController::class, 'cancel'])->name('cancel');
        });
    });

    // ══════════════════════════════════════════════════════════════════════
    // DISKON, KREDIT & LAPORAN PENJUALAN — owner, finance
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('role:finance')->group(function () {
        Route::prefix('sales/reports')->name('sales.reports.')->group(function () {
            Route::get('/', [ReportingController::class, 'index'])->name('index');
        });

        Route::prefix('sales/discounts')->name('sales.discounts.')->group(function () {
            Route::get('/',              [DiscountApprovalController::class, 'index'])->name('index');
            Route::get('/{id}',          [DiscountApprovalController::class, 'show'])->where('id', '.*')->name('show');
            Route::post('/{id}/approve', [DiscountApprovalController::class, 'approve'])->where('id', '.*')->name('approve');
            Route::post('/{id}/reject',  [DiscountApprovalController::class, 'reject'])->where('id', '.*')->name('reject');
        });

        Route::prefix('sales/credit')->name('sales.credit.')->group(function () {
            Route::get('/',          [CreditControlController::class, 'index'])->name('index');
            Route::get('/{id}',      [CreditControlController::class, 'show'])->name('show');
            Route::post('/validate', [CreditControlController::class, 'validate'])->name('validate');
        });
    });

    // Backward-compat Sales aliases (placed AFTER specific /sales/* prefixes)
    Route::middleware('role:finance,host')->group(function () {
        Route::get('/sales',               fn() => redirect()->route('sales.orders.index'))->name('sales.index');
        Route::get('/sales/create',        fn() => redirect()->route('sales.orders.create'))->name('sales.create');
        Route::post('/sales',              [SalesOrderController::class, 'store'])->name('sales.store');
        Route::get('/sales/{id}',          fn($id) => redirect()->route('sales.orders.show', $id))
            ->where('id', '(?!customers|quotations|orders|reports|discounts|credit).*')
            ->name('sales.show');
        Route::get('/sales/{id}/edit',     fn($id) => redirect()->route('sales.orders.edit', $id))
            ->where('id', '(?!customers|quotations|orders|reports|discounts|credit).*')
            ->name('sales.edit');
        Route::patch('/sales/{id}',        [SalesOrderController::class, 'update'])->name('sales.update');
        Route::post('/sales/{id}/confirm', [SalesOrderController::class, 'confirm'])->name('sales.confirm');
        Route::post('/sales/{id}/cancel',  [SalesOrderController::class, 'cancel'])->name('sales.cancel');
    });

    // ══════════════════════════════════════════════════════════════════════
    // PRODUK / MENU — kelola: owner, warehouse, procurement; lihat: semua staf
    // ══════════════════════════════════════════════════════════════════════
    Route::prefix('products')->name('products.')->group(function () {
        Route::middleware('role:warehouse,procurement')->group(function () {
            Route::get('/create',    [ProductController::class, 'create'])->name('create');
            Route::post('/',         [ProductController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [ProductController::class, 'edit'])->name('edit');
            Route::patch('/{id}',    [ProductController::class, 'update'])->name('update');
        });
        Route::get('/',     [ProductController::class, 'index'])->name('index');
        Route::get('/{id}', [ProductController::class, 'show'])->name('show');
    });

    // ══════════════════════════════════════════════════════════════════════
    // INVOICE & PEMBAYARAN
    // - Lihat & Pembayaran Kasir: owner, finance, cashier
    // - Posting Jurnal / Cancel / Buat Faktur dari DO/SO/Receipt: owner, finance
    // ══════════════════════════════════════════════════════════════════════
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::middleware('role:finance,cashier')->group(function () {
            Route::get('/',         [InvoiceController::class, 'index'])->name('index');
            Route::get('/{id}/pay', [InvoiceController::class, 'payForm'])->where('id', '.*')->name('pay.form');
            Route::post('/{id}/pay',[InvoiceController::class, 'pay'])->where('id', '.*')->name('pay');
        });
        Route::middleware('role:finance')->group(function () {
            Route::post('/{id}/post',           [InvoiceController::class, 'post'])->where('id', '.*')->name('post');
            Route::post('/{id}/cancel',         [InvoiceController::class, 'cancel'])->where('id', '.*')->name('cancel');
            Route::post('/from-delivery/{did}', [InvoiceController::class, 'createFromDelivery'])->where('did', '.*')->name('createFromDelivery');
            Route::post('/from-so/{so_id}',     [InvoiceController::class, 'createFromSalesOrder'])->where('so_id', '.*')->name('createFromSO');
            Route::post('/from-receipt/{rid}',  [InvoiceController::class, 'createFromReceipt'])->where('rid', '.*')->name('createFromReceipt');
        });
        Route::middleware('role:finance,cashier')->group(function () {
            Route::get('/{id}', [InvoiceController::class, 'show'])->where('id', '.*')->name('show');
        });
    });

    // Return / Credit Note — owner, finance, warehouse
    Route::middleware('role:finance,warehouse')->group(function () {
        Route::prefix('returns')->name('returns.')->group(function () {
            Route::get('/',               [InvoiceController::class, 'returnsIndex'])->name('index');
            Route::get('/create',         [InvoiceController::class, 'returnCreate'])->name('create');
            Route::post('/',              [InvoiceController::class, 'returnStore'])->name('store');
            Route::get('/{id}',           [InvoiceController::class, 'returnShow'])->where('id', '.*')->name('show');
            Route::post('/{id}/validate', [InvoiceController::class, 'returnValidate'])->where('id', '.*')->name('validate');
        });
    });

    // ══════════════════════════════════════════════════════════════════════
    // INVENTORI — owner, warehouse
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('role:warehouse')->group(function () {
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/',                           [InventoryController::class, 'index'])->name('index');
            Route::get('/transfer',                   [InventoryController::class, 'transferForm'])->name('transfer.create');
            Route::post('/transfer',                  [InventoryController::class, 'storeTransfer'])->name('transfer.store');
            Route::get('/deliveries',                 [InventoryController::class, 'deliveries'])->name('deliveries');
            Route::get('/deliveries/{did}',           [InventoryController::class, 'showDelivery'])->where('did', '.*')->name('delivery.show');
            Route::post('/deliveries/{did}/validate', [InventoryController::class, 'validateDelivery'])->where('did', '.*')->name('delivery.validate');
        });
    });

    // ══════════════════════════════════════════════════════════════════════
    // PEMBELIAN — PO: owner, procurement; Penerimaan QC: +warehouse; Lihat: +finance
    // ══════════════════════════════════════════════════════════════════════
    Route::prefix('purchase')->name('purchase.')->group(function () {
        Route::middleware('role:procurement')->group(function () {
            Route::get('/create',        [PurchaseController::class, 'create'])->name('create');
            Route::post('/',             [PurchaseController::class, 'store'])->name('store');
            Route::get('/{id}/edit',     [PurchaseController::class, 'edit'])->name('edit');
            Route::patch('/{id}',        [PurchaseController::class, 'update'])->name('update');
            Route::post('/{id}/confirm', [PurchaseController::class, 'confirm'])->name('confirm');
            Route::post('/{id}/cancel',  [PurchaseController::class, 'cancel'])->name('cancel');
        });
        Route::middleware('role:procurement,warehouse')->group(function () {
            Route::get('/{id}/receipt',             [PurchaseController::class, 'createReceipt'])->name('receipt.create');
            Route::post('/{id}/receipt',            [PurchaseController::class, 'storeReceipt'])->name('receipt.store');
            Route::post('/receipts/{rid}/validate', [PurchaseController::class, 'validateReceipt'])->where('rid', '.*')->name('receipt.validate');
        });
        Route::middleware('role:procurement,warehouse,finance')->group(function () {
            Route::get('/',               [PurchaseController::class, 'index'])->name('index');
            Route::get('/receipts/{rid}', [PurchaseController::class, 'showReceipt'])->where('rid', '.*')->name('receipt.show');
            Route::get('/{id}',           [PurchaseController::class, 'show'])->name('show');
        });
    });

    // ══════════════════════════════════════════════════════════════════════
    // AKUNTANSI — owner, finance
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('role:finance')->group(function () {
        Route::prefix('accounting')->name('accounting.')->group(function () {
            Route::get('/',             [AccountingController::class, 'index'])->name('index');
            Route::get('/ledger',       [AccountingController::class, 'ledger'])->name('ledger');
            Route::get('/entries',      [AccountingController::class, 'entries'])->name('entries');
            Route::get('/entries/{id}', [AccountingController::class, 'showEntry'])->where('id', '.*')->name('entry.show');
        });
    });

    // ══════════════════════════════════════════════════════════════════════
    // KARYAWAN
    //   - Semua role terautentikasi boleh melihat daftar & detail (SR-14:
    //     data privat disembunyikan di controller/view berdasar role)
    //   - Tambah/ubah hanya Owner — dijaga ganda: route middleware + controller
    // ══════════════════════════════════════════════════════════════════════
    Route::prefix('employees')->name('employees.')->group(function () {
        // Hanya owner — EmployeeController::authorizeOwner() juga memvalidasi ini
        Route::get('/create',    [EmployeeController::class, 'create'])->name('create');
        Route::post('/',         [EmployeeController::class, 'store'])->name('store');

        // Semua role: index & show (data privat dikontrol di view per SR-14)
        Route::get('/',          [EmployeeController::class, 'index'])->name('index');
        Route::get('/{id}',      [EmployeeController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::patch('/{id}',    [EmployeeController::class, 'update'])->name('update');
    });

    // ══════════════════════════════════════════════════════════════════════
    // RESERVASI — owner, host
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('role:host')->group(function () {
        Route::prefix('bookings')->name('bookings.')->group(function () {
            Route::get('/',              [BookingController::class, 'index'])->name('index');
            Route::get('/create',        [BookingController::class, 'create'])->name('create');
            Route::post('/',             [BookingController::class, 'store'])->name('store');
            Route::get('/{id}',          [BookingController::class, 'show'])->name('show');
            Route::get('/{id}/edit',     [BookingController::class, 'edit'])->name('edit');
            Route::patch('/{id}',        [BookingController::class, 'update'])->name('update');
            Route::post('/{id}/confirm', [BookingController::class, 'confirm'])->name('confirm');
            Route::post('/{id}/checkin', [BookingController::class, 'checkin'])->name('checkin');
            Route::post('/{id}/cancel',  [BookingController::class, 'cancel'])->name('cancel');
        });
    });

    // ══════════════════════════════════════════════════════════════════════
    // TABLE ORDER / POS — owner, host, server
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('role:host,server,cashier')->group(function () {
        Route::prefix('table-orders')->name('table-orders.')->group(function () {
            Route::get('/',            [TableOrderController::class, 'index'])->name('index');
            Route::get('/create',      [TableOrderController::class, 'create'])->name('create');
            Route::post('/',           [TableOrderController::class, 'store'])->name('store');
            Route::get('/{id}',        [TableOrderController::class, 'show'])->where('id', '.*')->name('show');
            Route::post('/{id}/line',   [TableOrderController::class, 'addLine'])->where('id', '.*')->name('line.add');
            Route::post('/{id}/serve',  [TableOrderController::class, 'serve'])->where('id', '.*')->name('serve');
            Route::post('/{id}/split',  [TableOrderController::class, 'splitBill'])->where('id', '.*')->name('split');
            Route::post('/{id}/bill',   [TableOrderController::class, 'bill'])->where('id', '.*')->name('bill');
            Route::post('/{id}/cancel', [TableOrderController::class, 'cancel'])->where('id', '.*')->name('cancel');
        });
    });

    // ══════════════════════════════════════════════════════════════════════
    // KONTAK — owner, finance, procurement, host
    // ══════════════════════════════════════════════════════════════════════
    Route::middleware('role:finance,procurement,host,cashier')->group(function () {
        Route::prefix('contacts')->name('contacts.')->group(function () {
            Route::get('/',          [ContactController::class, 'index'])->name('index');
            Route::get('/create',    [ContactController::class, 'create'])->name('create');
            Route::post('/',         [ContactController::class, 'store'])->name('store');
            Route::get('/{id}',      [ContactController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [ContactController::class, 'edit'])->name('edit');
            Route::patch('/{id}',    [ContactController::class, 'update'])->name('update');
        });
    });

});
