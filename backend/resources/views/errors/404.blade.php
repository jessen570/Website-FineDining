<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Tidak Ditemukan — Maison Aster ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background: #F7F5F0; }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <div style="max-width:480px;width:100%;background:#fff;border:1px solid #E8E4DA;border-radius:16px;
                box-shadow:0 4px 24px rgba(24,56,46,0.07);overflow:hidden">
        <div style="height:4px;background:linear-gradient(90deg,#18382E 0%,#C6A76B 50%,#18382E 100%)"></div>
        <div style="padding:40px 36px;text-align:center">
            <div style="width:72px;height:72px;border-radius:50%;background:#F0EEE8;border:2px solid #E8E4DA;
                        display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#8A8478" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </div>
            <div style="font-size:11px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#B8B2A6;margin-bottom:8px">ERROR 404</div>
            <h1 style="font-family:'Playfair Display',serif;font-size:24px;font-weight:600;color:#18382E;margin:0 0 10px">Halaman Tidak Ditemukan</h1>
            <p style="font-size:13.5px;color:#6B6560;line-height:1.65;margin:0 0 28px">
                Dokumen atau halaman yang Anda cari tidak tersedia atau sudah dihapus.
            </p>
            <div style="display:flex;gap:10px;justify-content:center">
                <a href="{{ route('dashboard') }}"
                   style="display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:7px;
                          background:#18382E;color:white;font-size:13px;font-weight:600;text-decoration:none">
                    Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
