<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWeb
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $response = $next($request);

        $this->recordPageViewIfApplicable($request, $response);

        return $response;
    }

    private function recordPageViewIfApplicable(Request $request, Response $response): void
    {
        if (
            app()->runningUnitTests() ||
            !$request->isMethod('GET') ||
            $request->expectsJson() ||
            $request->ajax() ||
            $response->getStatusCode() !== 200
        ) {
            return;
        }

        $path = '/' . ltrim($request->path(), '/');
        if ($path === '/activity-logs' || str_starts_with($path, '/activity-logs/')) {
            return;
        }

        $user = Auth::user();
        if (!$user) {
            return;
        }

        // Prevent duplicate VIEW logs within 60 seconds for the same page & user
        $recentExists = AuditLog::where('action', 'VIEW')
            ->where('actor_name', $user->name)
            ->where('reference_code', $path)
            ->where('created_at', '>=', now()->subSeconds(60))
            ->exists();

        if ($recentExists) {
            return;
        }

        [$docType, $docId, $message] = $this->resolvePageContext($path);

        try {
            AuditLog::log(
                'VIEW',
                $docType,
                $docId,
                $path,
                null,
                null,
                $message,
                $user->name,
                $user->role
            );
        } catch (\Throwable) {
            // Do not interrupt page rendering if logging fails
        }
    }

    private function resolvePageContext(string $path): array
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $first    = $segments[0] ?? 'dashboard';
        $second   = $segments[1] ?? null;
        $third    = $segments[2] ?? null;

        return match (true) {
            $first === 'dashboard' => ['Dashboard', 'DASHBOARD', 'Membuka halaman Dashboard Eksekutif'],
            $first === 'crm' && $second === 'leads' => [
                'CrmLead',
                $third && $third !== 'create' ? $third : 'LIST',
                $third === 'create' ? 'Membuka form Tambah Prospek' : ($third ? "Membuka detail Prospek {$third}" : 'Membuka daftar Prospek (Lead)'),
            ],
            $first === 'crm' && $second === 'opportunities' => [
                'CrmOpportunity',
                $third && $third !== 'create' ? $third : 'LIST',
                $third === 'create' ? 'Membuka form Tambah Peluang' : ($third ? "Membuka detail Peluang {$third}" : 'Membuka daftar Peluang (Opportunity)'),
            ],
            $first === 'crm' && $second === 'pipeline' => ['CrmPipeline', 'PIPELINE', 'Membuka papan Kanban Pipeline CRM'],
            $first === 'sales' && $second === 'customers' => [
                'Contact',
                $third && $third !== 'create' ? $third : 'LIST',
                $third ? "Membuka profil Pelanggan {$third}" : 'Membuka daftar Pelanggan',
            ],
            $first === 'sales' && $second === 'quotations' => [
                'Quotation',
                $third && $third !== 'create' ? $third : 'LIST',
                $third === 'create' ? 'Membuka form Buat Quotation' : ($third ? "Membuka detail Quotation {$third}" : 'Membuka daftar Surat Penawaran'),
            ],
            $first === 'sales' && $second === 'orders' => [
                'SalesOrder',
                $third && $third !== 'create' ? $third : 'LIST',
                $third === 'create' ? 'Membuka form Buat Sales Order' : ($third ? "Membuka detail Sales Order {$third}" : 'Membuka daftar Sales Order'),
            ],
            $first === 'sales' && $second === 'discounts' => [
                'DiscountApproval',
                count($segments) > 2 ? implode('/', array_slice($segments, 2)) : 'LIST',
                count($segments) > 2 ? 'Membuka detail Persetujuan Diskon ' . implode('/', array_slice($segments, 2)) : 'Membuka daftar Persetujuan Diskon',
            ],
            $first === 'sales' && $second === 'credit' => [
                'CreditControl',
                $third ?: 'LIST',
                $third ? "Membuka profil Kontrol Kredit {$third}" : 'Membuka daftar Kontrol Kredit',
            ],
            $first === 'sales' && $second === 'reports' => ['SalesReport', 'REPORT', 'Membuka halaman Laporan Penjualan'],
            $first === 'products' => [
                'Product',
                $second && $second !== 'create' ? $second : 'LIST',
                $second ? "Membuka detail Menu/Produk {$second}" : 'Membuka katalog Menu & Produk',
            ],
            $first === 'inventory' => [
                'Inventory',
                $second ?: 'STOCK',
                match ($second) {
                    'transfer'   => 'Membuka form Mutasi Stok',
                    'deliveries' => $third ? "Membuka detail Surat Jalan {$third}" : 'Membuka daftar Surat Jalan (DO)',
                    default      => 'Membuka halaman Persediaan Stok',
                },
            ],
            $first === 'purchase' => [
                'PurchaseOrder',
                $second && $second !== 'create' ? implode('/', array_slice($segments, 1)) : 'LIST',
                $second ? "Membuka halaman Pembelian ({$second})" : 'Membuka daftar Purchase Order (PO)',
            ],
            $first === 'invoices' => [
                'Invoice',
                count($segments) > 1 ? implode('/', array_slice($segments, 1)) : 'LIST',
                count($segments) > 1 ? 'Membuka detail Faktur ' . implode('/', array_slice($segments, 1)) : 'Membuka daftar Faktur & Tagihan',
            ],
            $first === 'returns' => [
                'ReturnOrder',
                $second ?: 'LIST',
                $second ? "Membuka halaman Retur {$second}" : 'Membuka daftar Retur Barang',
            ],
            $first === 'accounting' => [
                'Accounting',
                $second ?: 'OVERVIEW',
                "Membuka halaman Akuntansi ({$path})",
            ],
            $first === 'employees' => [
                'Employee',
                $second && $second !== 'create' ? $second : 'LIST',
                $second === 'create' ? 'Membuka form Tambah Karyawan' : ($second ? "Membuka profil Karyawan {$second}" : 'Membuka daftar Karyawan'),
            ],
            $first === 'bookings' => [
                'Booking',
                $second && $second !== 'create' ? $second : 'LIST',
                $second === 'create' ? 'Membuka form Buat Reservasi Meja' : ($second ? "Membuka detail Reservasi {$second}" : 'Membuka daftar Reservasi Meja'),
            ],
            $first === 'table-orders' => [
                'TableOrder',
                count($segments) > 1 ? implode('/', array_slice($segments, 1)) : 'LIST',
                count($segments) > 1 ? 'Membuka Pesanan Meja ' . implode('/', array_slice($segments, 1)) : 'Membuka halaman Pesanan Meja / POS',
            ],
            $first === 'contacts' => [
                'Contact',
                $second && $second !== 'create' ? $second : 'LIST',
                $second ? "Membuka detail Kontak {$second}" : 'Membuka daftar Kontak & Relasi',
            ],
            default => ['Page', $path, "Membuka halaman {$path}"],
        };
    }
}
