# Maison Aster Fine Dining — ERP System

Sistem Enterprise Resource Planning (ERP) web-based modern untuk **Maison Aster Fine Dining**, dirancang mengacu pada prinsip proses bisnis, keterlacakan, dan struktur antarmuka Odoo Web 17.0 (desktop browser).

---

## 1. Ringkasan Eksekutif & Karakteristik Bisnis
- **Entitas**: Maison Aster (Restoran Fine Dining, Jakarta Selatan, beroperasi sejak 2019).
- **Kapasitas**: 1 outlet, 22 kursi (*seats*), area meja *Main Dining*, *Window*, dan *Private Room*.
- **Lokasi Persediaan**: `KITCH-JKT` (dapur utama / prepared items) dan `BAR-JKT` (area minuman / beverage items).
- **Mata Uang & Pajak**: IDR (Rupiah bulat); PBJT Makanan & Minuman 10% dihitung terpisah dari subtotal penjualan. Pajak pembelian latihan 0% (fixture audit).
- **Baseline Inventaris**: 277 unit dengan nilai buku standar **Rp10.666.000** (`KITCH-JKT`: 127 unit / Rp8.301.000; `BAR-JKT`: 150 unit / Rp2.365.000).

---

## 2. Arsitektur Sistem

Sistem dipisahkan secara tegas (*decoupled architecture*) menjadi dua repositori/aplikasi:

```
Website FineDining/
├── backend/                  # Laravel 12 REST API & Business Logic Engine
│   ├── app/
│   │   ├── Http/Controllers/ # REST API Controllers
│   │   ├── Models/           # Eloquent Models & Business Methods
│   │   └── Services/         # ERP Core Services (Inventory, Accounting, Billing, Booking)
│   ├── database/
│   │   ├── migrations/       # PostgreSQL Schema Migrations
│   │   └── seeders/          # Fresh Baseline Dataset Seeders
│   ├── routes/api.php        # API Endpoints
│   └── config/               # Database, CORS, Sanctum configuration
│
├── frontend/                 # React 19 + TypeScript + Vite SPA
│   ├── src/
│   │   ├── components/       # UI & Odoo-style Widgets (Topbar, Sidebar, ControlPanel, AppSwitcher)
│   │   ├── pages/            # ERP Views (S-01 through S-18)
│   │   ├── services/         # Axios API Client & Authentication
│   │   ├── types/            # TypeScript Domain Definitions
│   │   └── index.css         # Tailwind CSS styling with Luxury Fine Dining Palette
│   └── vite.config.ts        # Bundler configuration
│
├── README.md                 # Dokumentasi Arsitektur, Setup, & Pengujian
└── .gitignore                # Git ignore rules untuk Backend & Frontend
```

### Palet Warna Desain (Luxury Fine Dining ERP):
- **Deep Navy (`#182638`)**: Warna dominan sidebar, topbar, dan header dokumen.
- **Warm Ivory (`#F7F5F0`)**: Latar belakang aplikasi (*canvas*) yang nyaman di mata.
- **Muted Gold (`#B89A63`)**: Aksen status bar aktif, smart button, tombol aksi utama, dan highlight status.
- **White (`#FFFFFF`)**: Kartu dokumen, form input, dan panel data.

---

## 3. Matriks Keputusan Bisnis (Decision Log & Defense)

| ID Aturan | Konflik Stakeholder | Pilihan Kebijakan | Keputusan & Alasan Bisnis | Mitigasi / Pihak Terdampak | Bukti Uji |
|:---|:---|:---|:---|:---|:---|
| **SR-01 vs SR-02** | Dion (Sales): Invoicing langsung saat SO confirmed vs Ratih (Finance): Invoice hanya atas barang yang sudah diserahkan (*Done*). | **SR-02 Diterapkan** (Kebijakan Invoicing Berbasis Delivered Quantity). | Restoran fine dining tidak boleh menagih tamu sebelum hidangan disajikan ke meja atau diserahkan fisik. Penagihan mendahului pengiriman berisiko sengketa pelanggan dan pengakuan pendapatan prematur. | Penjualan korporat memerlukan delivery note parsial/lengkap sebelum invoice terbit. Sales dapat melihat status SO "To Invoice". | T-17, T-18 |
| **SR-03 vs SR-04** | Arga (Resto Mgr): VIP boleh tumpang-tindih meja 30 menit vs Sari (Host): Jadwal meja absolut bebas tabrakan + buffer 15 menit. | **SR-04 & SR-22 Diterapkan** (No-clash policy with 120m window + 15m buffer). | Fine dining mengutamakan kepastian layanan dan kebersihan meja (*turnover* 15 menit). Mengizinkan irisan reservasi berisiko tamu VIP berdiri menunggu atau meja kotor, merusak reputasi restoran. | VIP dapat diprioritaskan alokasi meja berkapasitas lebih besar (misal TB-04 Private Room) tanpa menabrak jadwal tamu lain. | T-29, T-30 |
| **SR-05 vs SR-06** | Nisa (Procurement): Bill boleh untuk semua pesanan PO vs Ratih (Finance): Bill hanya untuk kuantitas yang lolos inspeksi (*Accepted*). | **SR-06 Diterapkan** (Strict 3-Way Matching). | Restoran hanya wajib membayar bahan segar dan lolos QC. Mencegah kerugian finansial akibat bahan mentah rusak atau ditolak (*Rejected*). | Vendor Bill otomatis membatasi kuantitas maksimum = `Accepted Quantity` pada Receipt terkait. Selisih harga dialokasikan ke Purchase Price Variance. | T-21, T-23, T-24 |
| **SR-07 vs SR-08** | Maya (Owner): Staf bebas beri diskon s.d 15% vs Ratih (Finance): Diskon di atas 10% wajib persetujuan Manajer. | **SR-08 Diterapkan** (Tiered Discount Governance). | Menjaga margin restoran dan mencegah penyalahgunaan wewenang pemberian diskon berlebihan tanpa pengawasan. | Server dapat memberi diskon langsung 0–10%. Diskon 11–15% membutuhkan input Approval ID Manager sebelum SO/Bill dapat di-confirm. | T-11, T-12 |
| **SR-09 & SR-10** | Bima (Warehouse): Larangan stok negatif & pemisahan On Hand vs Reserved. | **SR-09 & SR-10 Diterapkan**. | SO Confirmed menaikkan `Reserved`, `Available = On Hand - Reserved`. On Hand baru berkurang saat Delivery `Done`. Jika Available < Order, proses diblokir. | Mencegah *overselling* dan kebingungan persediaan dapur saat jam sibuk. | T-05, T-13, T-14, T-15 |
| **SR-15** | Dion (Sales): Pelanggan korporat boleh kredit vs Kontrol Piutang. | **SR-15 Diterapkan** (Strict Credit Exposure Check). | Total piutang terbuka (Opening AR + unpaid Invoice + Confirmed uninvoiced SO + draft calon order) tidak boleh melampaui `credit_limit`. Dihitung *single-entry* tanpa duplikasi. | Pelanggan dengan limit terlampaui harus melunasi invoice tertunggak sebelum order baru disahkan. | T-04, T-12 |
| **SR-20 & SR-24** | Idempotent Posting & Immutability Audit Trail. | **Diterapkan secara mutlak**. | Menekan tombol posting dua kali tidak boleh menciptakan jurnal ganda atau mengurangi stok dua kali. Dokumen `Confirmed`/`Posted`/`Done` terkunci dari pengubahan langsung. | Audit log merekam aktor, stempel waktu, aksi, referensi dokumen, dan nilai sebelum/sesudah. | T-02, T-06, T-16, T-20, T-28, T-34 |

---

## 4. Alur Bisnis Utama (*Core Flows*)

1. **Order to Cash (O2C)**:
   `Lead` → `Opportunity` → `Quotation` → (Credit Check & Discount Gate) → `Sales Order (Confirmed)` → `Delivery Order (Done)` → `Customer Invoice (Posted)` → `Register Payment`
2. **Procure to Pay (P2P)**:
   `RFQ` → `Purchase Order (Confirmed)` → `Receipt (Inspected: Accepted / Rejected)` → `Vendor Bill (3-Way Matched)` → `Payment`
3. **Fine Dining Reservation to Payment**:
   `Customer Booking` (Slot 120m + 15m Buffer) → `Table Assignment` (Host) → `Check-In` → `Table Order` (Server Aktif) → `Kitchen / Bar Delivery (Done)` → `Customer Invoice (Split Support)` → `Payment`
4. **Return and Credit Note**:
   `Return Request` → `Quality Inspection` → `Return Stock Move` (Kembali ke On Hand) → `Credit Note (Posted)` → `Receivable & COGS Adjustment`

---

## 5. Prasyarat & Instalasi Lokal

### Kebutuhan Perangkat Lunak:
- **PHP**: Versi 8.2 atau lebih baru dengan ekstensi `pdo_pgsql`, `pgsql`, `openssl`, `mbstring`, `curl`.
- **Composer**: Versi 2.x.
- **Node.js**: Versi 20.x atau 24.x LTS dan `npm`.
- **PostgreSQL**: PostgreSQL 14/15/16/17 (Lokal atau Cloud Managed PostgreSQL seperti Neon/Supabase/Railway).

---

### Langkah Setup Backend (Laravel API):

1. Masuk ke direktori `backend`:
   ```powershell
   cd backend
   ```
2. Salin environment file:
   ```powershell
   copy .env.example .env
   ```
3. Sesuaikan kredensial PostgreSQL pada `.env`:
   ```ini
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=maison_aster_erp
   DB_USERNAME=postgres
   DB_PASSWORD=your_postgres_password
   DB_SSLMODE=prefer
   ```
   *Atau jika menggunakan PostgreSQL Cloud (Neon, Railway, Supabase):*
   ```ini
   DB_URL=postgresql://user:password@ep-sample.aws.neon.tech/neondb?sslmode=require
   ```
4. Pastikan dependencies terinstal & generate application key:
   ```powershell
   composer install
   php artisan key:generate
   ```
5. Jalankan migrasi dan baseline seeder (Tahap 2):
   ```powershell
   php artisan migrate:fresh --seed
   ```
6. Jalankan local development server:
   ```powershell
   php artisan serve --port=8000
   ```
   *API siap diakses pada `http://127.0.0.1:8000/api`.*

---

### Langkah Setup Frontend (React + TypeScript + Vite):

1. Masuk ke direktori `frontend`:
   ```powershell
   cd frontend
   ```
2. Pastikan file `.env` mengarah ke backend API:
   ```ini
   VITE_API_BASE_URL=http://127.0.0.1:8000/api
   ```
3. Instal dependencies (jika belum):
   ```powershell
   npm install
   ```
4. Jalankan Vite development server:
   ```powershell
   npm run dev
   ```
   *Frontend siap dibuka pada browser desktop di `http://localhost:5173`.*

5. Verifikasi build produksi frontend:
   ```powershell
   npm run build
   ```

---

## 6. Akun Uji Coba Berdasarkan Peran (*Role Test Credentials*)

| Peran (*Role*) | Staf Terkait | Job Position | Akses Utama Menu | Kredensial Login |
|:---|:---|:---|:---|:---|
| **HR / General Manager** | Maya Atmadja (`E-01`) | General Manager | Seluruh Menu & Data Pribadi Karyawan | `owner@maisonaster.id` / `Password123!` |
| **Host** | Sari Amalia (`E-02`) | Host | Dining Booking, Meja, Reservasi, Overview | `host@maisonaster.id` / `Password123!` |
| **Server** | Raka Putra (`E-04`) | Server | Floor Plan, Table Order, Pelayanan Meja | `server@maisonaster.id` / `Password123!` |
| **Warehouse Officer** | Bima Santosa (`E-06`) | Warehouse Officer | Persediaan, Mutasi Lokasi, Delivery, Receipt | `warehouse@maisonaster.id` / `Password123!` |
| **Finance Manager** | Ratih Prameswari (`E-07`)| Finance Manager | Invoice, Bill, Payment, Jurnal, CoA, Credit Check | `finance@maisonaster.id` / `Password123!` |
| **Purchasing Lead** | Nisa Damayanti (`E-08`)| Purchasing Lead | RFQ, Purchase Order, Vendor Pricelist | `procurement@maisonaster.id` / `Password123!` |
| **Cashier** | Dita Anggraini (`E-05`)| Cashier | Kasir Restoran, Payment Registration | `cashier@maisonaster.id` / `Password123!` |
| *Inactive Staff* | Eka Wardana (`E-09`)| Server (Tidak Aktif)| Akses ditolak untuk penugasan layanan | *(Uji validasi status)* |

---

## 7. Rencana Pengujian Komprehensif (T-01 s.d. T-36)

Sistem memuat 36 skenario pengujian ketat (*36 Test Cases*) yang memverifikasi integritas logika bisnis:

- **T-01 & T-02**: Otentikasi Host & pembatasan akses Warehouse atas Journal Entry.
- **T-03 & T-04**: Registrasi Customer baru & evaluasi batas kredit C-003.
- **T-05 & T-06**: Tampilan stok per lokasi FD-003 & penguncian harga SO S00007.
- **T-07 & T-08**: Penugasan server aktif E-04 vs penolakan E-09 & perlindungan data pribadi E-04.
- **T-09 & T-10**: Konversi CRM Lead L0001 menjadi Opportunity & pencegahan duplikasi.
- **T-11 & T-12**: Konfirmasi SO C-001 & penolakan order over-limit C-003 serta diskon 15% tanpa approval.
- **T-13 & T-14**: Mutasi stok BAR ke KITCH & penolakan mutasi stok negatif.
- **T-15 & T-16**: Partial Delivery 1 unit FD-001 & proteksi eksekusi berulang (*idempotency*).
- **T-17 & T-18**: Penagihan invoice atas kuantitas terkirim & penolakan penagihan unit backorder.
- **T-19 & T-20**: Konfirmasi PO FD-006 & proteksi pengubahan harga PO terkonfirmasi.
- **T-21 & T-22**: Receipt dengan Accepted 4 + Rejected 1 & penolakan stok untuk barang cacat.
- **T-23 & T-24**: Vendor Bill 3-way match & pemblokiran penagihan melebihi kuantitas Accepted.
- **T-25 & T-26**: Registrasi pembayaran parsial & penolakan overpayment vendor bill.
- **T-27 & T-28**: Verifikasi keseimbangan debit/kredit jurnal umum & pencegahan duplikasi jurnal.
- **T-29 & T-30**: Reservasi meja TB-02 4 pax & penegakan deteksi benturan waktu + buffer 15 menit.
- **T-31 & T-32**: Table Order dan split bill seimbang & penolakan order server non-aktif.
- **T-33 & T-34**: Retur hidangan, pemulihan stok, credit note, reversal HPP & pencegahan retur ganda.
- **T-35 & T-36**: Verifikasi KPI dashboard awal & pembatasan drill-down piutang untuk peran warehouse.

---

## 8. Panduan Deployment Produksi

### Frontend ke Vercel:
1. Hubungkan repositori GitHub ke Vercel.
2. Atur Root Directory ke `frontend`.
3. Framework Preset: `Vite`.
4. Tambahkan Environment Variable:
   - `VITE_API_BASE_URL`: URL API publik backend Laravel (contoh: `https://api.maisonaster.yourdomain.com/api`).
5. Build Command: `npm run build`, Output Directory: `dist`.

### Backend ke Cloud Platform PHP (Railway / Render / VPS):
1. Deploy folder `backend`.
2. Pasang environment variables produksi:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_KEY=base64:...`
   - `APP_URL=https://api.maisonaster.yourdomain.com`
   - `DB_CONNECTION=pgsql`
   - `DB_URL=postgresql://...`
   - `FRONTEND_URL=https://maisonaster.vercel.app`
3. Jalankan `php artisan migrate --force` dan seeder baseline.
