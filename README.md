# Maison Aster Fine Dining ERP

Sistem ERP terintegrasi untuk restoran fine dining berbasis **Laravel 12 / PHP 8.2** dengan database **MySQL (XAMPP)**.

---

## Daftar Isi

1. [Persyaratan Sistem](#persyaratan-sistem)
2. [Instalasi Cepat (XAMPP)](#instalasi-cepat-xampp)
3. [Konfigurasi `.env`](#konfigurasi-env)
4. [Migrasi & Seeding Database](#migrasi--seeding-database)
5. [Menjalankan Server Lokal](#menjalankan-server-lokal)
6. [Akun Login Uji](#akun-login-uji)
7. [Modul & Fitur](#modul--fitur)
8. [Aturan Bisnis Kunci](#aturan-bisnis-kunci)
9. [Struktur Folder](#struktur-folder)
10. [Troubleshooting](#troubleshooting)

---

## Persyaratan Sistem

| Komponen | Versi Minimum |
|---|---|
| PHP | 8.2+ |
| Laravel | 12.x |
| MySQL | 8.0+ (via XAMPP) |
| Composer | 2.x |
| Node.js | 18+ (untuk assets) |
| XAMPP | 8.2+ |

---

## Instalasi Cepat (XAMPP)

### Langkah 1 — Kloning / Siapkan Proyek

```bash
# Jika menggunakan Git:
git clone <repo-url> Website-FineDining-1
cd Website-FineDining-1/backend

# Atau buka folder backend di terminal
cd d:\Website-FineDining-1\backend
```

### Langkah 2 — Install Dependensi PHP

```bash
composer install
```

### Langkah 3 — Buat File `.env`

```bash
# Copy dari template
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Langkah 4 — Buat Database di XAMPP

1. Buka **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Buat database baru: **`maison_aster_erp`**
3. Collation: `utf8mb4_unicode_ci`

### Langkah 5 — Edit `.env` (Sesuaikan Koneksi)

```env
APP_NAME="Maison Aster ERP"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=maison_aster_erp
DB_USERNAME=root
DB_PASSWORD=          # Kosong jika XAMPP default, isi jika ada password
```

---

## Migrasi & Seeding Database

Jalankan **dari dalam folder `backend/`**:

```bash
# Migrasi + Seed sekaligus (rekomendasi pertama kali)
php artisan migrate --seed

# Atau jika ingin reset bersih (HAPUS semua data lama):
php artisan migrate:fresh --seed
```

**Yang akan di-seed:**

| Data | Keterangan |
|---|---|
| 9 Karyawan | E-01 s.d. E-09 (E-09 = Tidak Aktif) |
| 7 User | Login uji dengan peran berbeda |
| 6 Pelanggan | C-001 s.d. C-006 |
| 4 Vendor | V-01 s.d. V-04 |
| 15 Produk | FD-001 s.d. FD-010 (makanan) + BV-011 s.d. BV-015 (minuman) |
| 6 Meja | TB-01 s.d. TB-06 (total 22 kursi) |
| 2 Lokasi Gudang | KITCH-JKT + BAR-JKT |
| Opening Stock | 277 unit · Rp 10.666.000 |
| Chart of Accounts | 15 akun (Aset, Liabilitas, Ekuitas, Pendapatan, Beban) |
| Opening Journal | JE/OPENING — Balance Rp 26.066.000 |
| CRM Lead | L0001 — PT Andalan Prima |
| Sales Order | S00007 — Dikonfirmasi (lock 2× FD-003) |

---

## Menjalankan Server Lokal

Pastikan **MySQL XAMPP sudah berjalan**, lalu:

```bash
# Dari folder backend/
php artisan serve
```

Aplikasi tersedia di: **http://localhost:8000**

> **Catatan XAMPP:** Jangan lupa start Apache + MySQL dari XAMPP Control Panel sebelum menjalankan `php artisan serve`.

### Menjalankan dengan Port Berbeda

```bash
php artisan serve --port=8080
```

---

## Akun Login Uji

Semua akun menggunakan password: **`Password123!`**

| Email | Peran | Hak Akses |
|---|---|---|
| `owner@maisonaster.id` | **Owner** | Akses penuh semua modul |
| `host@maisonaster.id` | **Host** | Booking, Table Order, Kontak |
| `server@maisonaster.id` | **Server** | Table Order (buka & sajikan) |
| `warehouse@maisonaster.id` | **Warehouse** | Inventori, Penerimaan Barang |
| `finance@maisonaster.id` | **Finance** | Invoice, Pembayaran, Akuntansi |
| `procurement@maisonaster.id` | **Procurement** | Purchase Order, Penerimaan |
| `cashier@maisonaster.id` | **Cashier** | Invoice & Pembayaran |

---

## Modul & Fitur

### F-01 & F-22 — Autentikasi & Audit Log
- Login berbasis sesi Laravel dengan peran berbeda
- Setiap aksi penting (CREATE, UPDATE, CONFIRM, CANCEL, dll.) dicatat ke `audit_logs`
- Audit Log menyimpan: Actor, Timestamp, Action, Before Payload, After Payload

### F-04 — Karyawan & Penugasan Aktif (SR-13, SR-14)
- **SR-13**: Hanya karyawan berstatus `Aktif` yang bisa dipilih sebagai server di Table Order dan Booking
- **SR-14**: Data privat karyawan (HP, email, alamat) hanya terlihat oleh role `owner` / `hr`
- Halaman: `/employees` · `/employees/create` · `/employees/{id}` · `/employees/{id}/edit`

### F-17 — Dining Booking / Reservasi (SR-03, SR-04, SR-22)
- **SR-22**: Durasi booking = 120 menit + 15 menit buffer pembersihan (total 135 menit)
- **SR-04**: Tolak otomatis jika ada konflik jadwal pada meja yang sama
- **SR-03**: Tamu VIP — irisan waktu maksimum 30 menit untuk meja yang sama
- Lifecycle: `Draft → Confirmed → Checked-In → Done`
- Halaman: `/bookings` · `/bookings/create` · `/bookings/{id}` · `/bookings/{id}/edit`

### F-18 — Table Order & Service (SR-08, SR-09, SR-13, SR-23)
- Menghubungkan Booking → Table Order → Customer Invoice
- **SR-08**: Diskon > 10% memerlukan approval Manager/Finance
- **SR-09**: Cek ketersediaan stok sebelum item dapat ditambahkan
- **SR-23**: Split bill didukung tanpa duplikasi item/subtotal; PBJT 10% diterapkan per baris
- Lifecycle: `Open → Served → Billed → Paid`
- Halaman: `/table-orders` · `/table-orders/create` · `/table-orders/{id}`

### CRM — Leads & Opportunities
- Pipeline stage: Lead → Qualified → Proposition → Negotiation → Won/Lost
- Konversi Lead ke Quotation / Sales Order
- Halaman: `/crm/leads` · `/crm/opportunities` · `/crm/pipeline`

### Sales — O2C (Order to Cash)
- Quotation → Sales Order → Delivery → Customer Invoice → Payment
- **SR-02**: Invoice hanya bisa dibuat setelah Delivery Order selesai (qty terkirim)
- Kontrol diskon dengan approval workflow
- Credit limit checking per pelanggan
- Halaman: `/sales/quotations` · `/sales/orders` · `/sales/discounts` · `/sales/credit`

### Inventory
- Transfer stok, delivery order, validasi pengiriman
- Stok terpisah per lokasi: `KITCH-JKT` (makanan) & `BAR-JKT` (minuman)
- Halaman: `/inventory`

### Purchase — P2P (Procure to Pay)
- Purchase Order → Receipt (penerimaan) → 3-Way Match → Vendor Bill
- **SR-05/SR-06**: Bill hanya bisa dibuat atas qty yang diterima & diinspeksi
- Halaman: `/purchase`

### Accounting
- Jurnal berpasangan otomatis untuk setiap transaksi (Invoice post, Payment, COGS)
- **Idempotency posting** — mencegah jurnal duplikat per invoice
- Reversal jurnal untuk retur (Credit Note)
- Halaman: `/accounting` · `/accounting/ledger` · `/accounting/entries`

### Invoicing & Returns
- Customer Invoice & Vendor Bill
- Retur dengan Credit Note + COGS reversal + stok dikembalikan
- Halaman: `/invoices` · `/returns`

---

## Aturan Bisnis Kunci

| Kode | Deskripsi |
|---|---|
| SR-02 | Invoice customer hanya bisa dibuat setelah DO Done |
| SR-03 | VIP booking: max 30 menit overlap waktu pada meja sama |
| SR-04 | Konflik jadwal booking biasa ditolak secara otomatis |
| SR-05/06 | 3-Way Match: Bill ≤ qty diterima & diinspeksi |
| SR-08 | Diskon > 10% memerlukan persetujuan Owner/Finance |
| SR-09 | Stok dicek sebelum item Table Order dapat ditambahkan |
| SR-13 | Hanya karyawan Aktif yang dapat ditugaskan |
| SR-14 | Data privat karyawan hanya terlihat Owner/HR |
| SR-16 | Pembayaran tidak boleh melebihi sisa tagihan |
| SR-20 | Idempotency posting invoice — jurnal tidak boleh duplikat |
| SR-22 | Booking: 120 menit layanan + 15 menit buffer |
| SR-23 | Split bill tanpa duplikasi item/subtotal; PBJT 10% per baris |

---

## Struktur Folder

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/              # AuthController (Sanctum)
│   │   │   └── Web/              # Semua controller web
│   │   │       ├── Crm/          # LeadController, OpportunityController, PipelineController
│   │   │       ├── Sales/        # CustomerController, QuotationController, SalesOrderController, …
│   │   │       ├── AccountingController.php
│   │   │       ├── BookingController.php
│   │   │       ├── EmployeeController.php
│   │   │       ├── InventoryController.php
│   │   │       ├── InvoiceController.php
│   │   │       ├── ProductController.php
│   │   │       ├── PurchaseController.php
│   │   │       └── TableOrderController.php
│   │   └── Middleware/
│   │       ├── AuthenticateWeb.php
│   │       ├── CheckRole.php
│   │       └── RedirectIfAuthenticated.php
│   └── Models/                   # 34 Eloquent Models
│
├── database/
│   ├── migrations/               # 19 file migrasi
│   └── seeders/
│       └── DatabaseSeeder.php    # Dataset lengkap Maison Aster
│
├── resources/
│   └── views/
│       ├── layouts/app.blade.php # Layout utama (Odoo-style sidebar + topbar)
│       ├── auth/login.blade.php
│       ├── dashboard/
│       ├── bookings/             # index, create, show, edit
│       ├── table-orders/         # index, create, show
│       ├── employees/            # index, create, show, edit
│       ├── crm/                  # leads/, opportunities/, pipeline/
│       ├── sales/                # customers/, quotations/, orders/, discounts/, credit/
│       ├── invoices/
│       ├── inventory/
│       ├── purchase/
│       ├── accounting/
│       ├── contacts/
│       └── products/
│
├── routes/
│   ├── web.php                   # 100+ rute web terautentikasi
│   └── api.php                   # Health check + Sanctum auth
│
├── .env.example
└── composer.json
```

---

## Troubleshooting

### Error: `SQLSTATE[HY000] [1049] Unknown database`
→ Buat database `maison_aster_erp` di phpMyAdmin terlebih dahulu.

### Error: `php_network_getaddresses: getaddrinfo failed`
→ Pastikan MySQL XAMPP sedang berjalan.

### Error: `Class not found` setelah composer install
```bash
composer dump-autoload
```

### Error: `419 Page Expired` saat login
→ Session driver butuh tabel `sessions`. Jalankan:
```bash
php artisan migrate
```
Pastikan `.env` memiliki `SESSION_DRIVER=database`.

### Stok tidak terpotong saat Table Order "Serve"
→ Pastikan produk ada di lokasi yang sesuai:
- `FD-xxx` → `KITCH-JKT`
- `BV-xxx` → `BAR-JKT`

### Reset data ke kondisi awal
```bash
php artisan migrate:fresh --seed
```
> ⚠️ Peringatan: Perintah ini akan **menghapus semua data** dan mengisi ulang dari seeder.

---

## Teknologi

- **Backend**: Laravel 12, PHP 8.2+
- **Database**: MySQL 8.0 (XAMPP)
- **Auth API**: Laravel Sanctum 4.0
- **Frontend**: Blade Templates, Tailwind CSS (CDN), Lucide Icons, Chart.js
- **UI Style**: Mengikuti pola Odoo 17.0 Web Desktop (sidebar, breadcrumb, status pipeline, audit log tab)

---

*Maison Aster Fine Dining ERP — v1.0.0*
