<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Akses Ditolak — Maison Aster ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F7F5F0; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <div style="max-width:520px;width:100%;background:#fff;border:1px solid #E8E4DA;border-radius:16px;
                box-shadow:0 4px 24px rgba(24,56,46,0.07);overflow:hidden">

        {{-- Top accent bar --}}
        <div style="height:4px;background:linear-gradient(90deg,#18382E 0%,#C6A76B 50%,#18382E 100%)"></div>

        <div style="padding:40px 36px;text-align:center">

            {{-- Icon --}}
            <div style="width:72px;height:72px;border-radius:50%;background:#FEF2F2;border:2px solid #FECACA;
                        display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>

            {{-- Error code --}}
            <div style="font-size:11px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;
                        color:#B8B2A6;margin-bottom:8px">ERROR 403</div>

            {{-- Title --}}
            <h1 style="font-family:'Playfair Display',serif;font-size:24px;font-weight:600;
                       color:#18382E;margin:0 0 10px">Akses Ditolak</h1>

            {{-- Message --}}
            <p style="font-size:13.5px;color:#6B6560;line-height:1.65;margin:0 0 24px">
                @if(!empty($exception) && $exception->getMessage())
                    {{ $exception->getMessage() }}
                @else
                    Peran Anda tidak memiliki izin untuk mengakses halaman ini.
                    Tindakan ini telah dicatat dalam Audit Log sistem.
                @endif
            </p>

            {{-- Role info --}}
            @auth
            <div style="background:#F7F5F0;border:1px solid #E8E4DA;border-radius:8px;
                        padding:10px 14px;margin-bottom:24px;font-size:12.5px;color:#8A8478;
                        display:flex;align-items:center;justify-content:center;gap:8px">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                Login sebagai <strong style="color:#343130">{{ auth()->user()->name }}</strong>
                dengan peran <strong style="color:#18382E">{{ ucfirst(auth()->user()->role) }}</strong>
            </div>
            @endauth

            {{-- Audit note --}}
            <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;
                        padding:9px 14px;margin-bottom:28px;font-size:12px;color:#78350F;
                        display:flex;align-items:flex-start;gap:8px;text-align:left">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     style="flex-shrink:0;margin-top:1px">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span>Percobaan akses ini telah dicatat otomatis di <strong>Audit Log</strong> (SR-24) dengan
                actor, waktu, dan halaman yang diminta.</span>
            </div>

            {{-- Actions --}}
            <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
                   style="display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:7px;
                          background:#F7F5F0;border:1px solid #DDD8CC;color:#4D4944;font-size:13px;
                          font-weight:600;text-decoration:none">
                    ← Kembali
                </a>
                <a href="{{ route('dashboard') }}"
                   style="display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:7px;
                          background:#18382E;color:white;font-size:13px;font-weight:600;text-decoration:none">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/>
                        <rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/>
                    </svg>
                    Dashboard
                </a>
            </div>
        </div>

        {{-- Footer --}}
        <div style="padding:12px 24px;border-top:1px solid #F0EEE8;background:#FAFAF9;
                    text-align:center;font-size:11px;color:#B8B2A6">
            Maison Aster Fine Dining ERP · Akses dikendalikan berdasarkan peran (SR-14, SR-24)
        </div>
    </div>
</body>
</html>
