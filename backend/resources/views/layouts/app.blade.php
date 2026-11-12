<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Maison Aster') — ERP</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    forest:  { 900:'#0D2218', 800:'#18382E', 700:'#1F4A3A', 600:'#244A3D', 500:'#2E5C4A', 400:'#3D7A62' },
                    gold:    { 900:'#7A5A2E', 800:'#96703A', 700:'#A88C58', 600:'#C6A76B', 500:'#D4B87A', 400:'#E2CC99' },
                    ivory:   { DEFAULT:'#F7F5F0', 100:'#F0EEE8', 200:'#E8E4DA', 300:'#DDD8CC' },
                    stone:   { 50:'#FAFAF9', 100:'#F5F4F1', 200:'#EAE8E3', 300:'#D6D2C9', 400:'#B8B2A6', 500:'#8A8478', 600:'#6B6560', 700:'#4D4944', 800:'#343130', 900:'#1E1C1A' },
                },
                fontFamily: {
                    ui:    ['Inter', 'system-ui', 'sans-serif'],
                    brand: ['"Playfair Display"', 'Georgia', 'serif'],
                },
                fontSize: {
                    '2xs': ['0.65rem', { lineHeight: '1rem' }],
                    'xs':  ['0.75rem', { lineHeight: '1.125rem' }],
                    'sm':  ['0.8125rem', { lineHeight: '1.25rem' }],
                    'base':['0.875rem', { lineHeight: '1.375rem' }],
                    'md':  ['0.9375rem', { lineHeight: '1.5rem' }],
                    'lg':  ['1.0625rem', { lineHeight: '1.625rem' }],
                    'xl':  ['1.1875rem', { lineHeight: '1.75rem' }],
                    '2xl': ['1.375rem',  { lineHeight: '2rem' }],
                    '3xl': ['1.75rem',   { lineHeight: '2.25rem' }],
                },
                boxShadow: {
                    'xs':  '0 1px 2px 0 rgba(0,0,0,0.04)',
                    'sm':  '0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.04)',
                    'md':  '0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.04)',
                    'lg':  '0 10px 15px -3px rgba(0,0,0,0.07), 0 4px 6px -4px rgba(0,0,0,0.04)',
                    'ring-gold': '0 0 0 3px rgba(198,167,107,0.2)',
                },
                borderRadius: {
                    'sm':  '6px',
                    DEFAULT:'8px',
                    'md':  '10px',
                    'lg':  '12px',
                    'xl':  '16px',
                },
            }
        }
    }
    </script>

    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo-icon.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
    /* ─── Base ────────────────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; }
    html { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    body { font-family: 'Inter', system-ui, sans-serif; background-color: #F7F5F0; color: #343130; font-size: 0.875rem; line-height: 1.5; }

    /* ─── Sidebar ─────────────────────────────────────────────────────── */
    #sidebar {
        width: 256px;
        background: #18382E; /* Deep Forest Green */
        border-right: 1px solid rgba(255,255,255,0.06);
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        height: 100vh;
        overflow-y: auto;
        scrollbar-width: none;
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    #sidebar::-webkit-scrollbar { display: none; }

    @media (max-width: 1023px) {
        #sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 50;
            transform: translateX(-105%);
            box-shadow: none;
            visibility: hidden;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.25s;
        }
        #sidebar.open {
            transform: translateX(0);
            box-shadow: 4px 0 24px rgba(0,0,0,0.3);
            visibility: visible;
        }
    }

    #sidebar-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 34, 27, 0.6);
        backdrop-filter: blur(2px);
        z-index: 45;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease;
    }
    #sidebar-backdrop.open {
        opacity: 1;
        pointer-events: auto;
    }

    .sidebar-brand {
        padding: 20px 20px 16px;
        border-bottom: 1px solid rgba(255,255,255,0.07);
    }
    .sidebar-monogram {
        width: 36px; height: 36px;
        background: #C6A76B;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(198,167,107,0.35);
    }
    .sidebar-brand-name {
        font-family: 'Playfair Display', Georgia, serif;
        font-weight: 600; font-size: 13px; letter-spacing: 0.08em;
        color: #fff; line-height: 1.2;
    }
    .sidebar-brand-sub {
        font-size: 9.5px; letter-spacing: 0.18em; text-transform: uppercase;
        color: rgba(198,167,107,0.55); margin-top: 2px;
    }

    .sidebar-nav { flex: 1; padding: 12px 10px; }

    .nav-section-label {
        padding: 14px 10px 6px;
        font-size: 9px; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase;
        color: rgba(255,255,255,0.28); user-select: none;
    }

    .nav-item {
        display: flex; align-items: center; gap: 10px;
        padding: 8px 10px; border-radius: 7px; margin-bottom: 1px;
        font-size: 13px; font-weight: 500; color: rgba(255,255,255,0.62);
        text-decoration: none; cursor: pointer;
        transition: background 0.12s ease, color 0.12s ease;
        position: relative;
    }
    .nav-item:hover { background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.92); }
    .nav-item.active {
        background: rgba(198,167,107,0.15);
        color: #D4B87A;
        border: 1px solid rgba(198,167,107,0.22);
    }
    .nav-item.active .nav-icon { color: #C6A76B; }
    .nav-icon { width: 16px; height: 16px; flex-shrink: 0; opacity: 0.75; }
    .nav-item.active .nav-icon,
    .nav-item:hover .nav-icon { opacity: 1; }
    .nav-badge {
        margin-left: auto;
        font-size: 10px; font-weight: 700;
        background: rgba(198,167,107,0.18); color: #C6A76B;
        padding: 1px 6px; border-radius: 100px;
        min-width: 18px; text-align: center;
    }
    .nav-badge.urgent { background: rgba(239,68,68,0.15); color: #F87171; }

    .sidebar-footer {
        padding: 14px 14px;
        border-top: 1px solid rgba(255,255,255,0.07);
        flex-shrink: 0;
    }
    .user-card {
        display: flex; align-items: center; gap: 10px;
        padding: 8px 10px; border-radius: 8px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.07);
    }
    .user-avatar {
        width: 30px; height: 30px; border-radius: 50%;
        background: linear-gradient(135deg, #244A3D, #3D7A62);
        border: 1.5px solid rgba(198,167,107,0.35);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        font-size: 11px; font-weight: 700; color: #D4B87A;
    }
    .user-name { font-size: 12.5px; font-weight: 600; color: rgba(255,255,255,0.88); }
    .user-role { font-size: 10px; color: rgba(255,255,255,0.38); text-transform: capitalize; margin-top: 1px; }

    /* ─── Topbar ──────────────────────────────────────────────────────── */
    #topbar {
        height: 52px;
        background: #FFFFFF;
        border-bottom: 1px solid #E8E4DA;
        display: flex; align-items: center;
        padding: 0 16px;
        flex-shrink: 0;
        gap: 12px;
    }
    @media (min-width: 640px) {
        #topbar {
            padding: 0 24px;
            gap: 16px;
        }
    }
    .breadcrumb { display: flex; align-items: center; gap: 6px; flex: 1; min-width: 0; }
    .breadcrumb-sep { color: #D6D2C9; font-size: 12px; }
    .breadcrumb-item { font-size: 12.5px; color: #8A8478; text-decoration: none; }
    .breadcrumb-item:hover { color: #343130; }
    .breadcrumb-item.current { color: #343130; font-weight: 600; }
    .topbar-search {
        display: flex; align-items: center; gap: 8px;
        background: #F7F5F0; border: 1px solid #E8E4DA; border-radius: 7px;
        padding: 6px 12px; width: 200px;
        font-size: 12.5px; color: #8A8478;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .topbar-search:focus-within { border-color: rgba(198,167,107,0.5); box-shadow: 0 0 0 3px rgba(198,167,107,0.1); }
    .topbar-search input { background: none; border: none; outline: none; font-size: 12.5px; color: #343130; width: 100%; font-family: inherit; }
    .topbar-search input::placeholder { color: #B8B2A6; }
    .topbar-divider { width: 1px; height: 20px; background: #E8E4DA; flex-shrink: 0; }
    .topbar-chip {
        display: flex; align-items: center; gap: 5px;
        font-size: 11.5px; font-weight: 500;
        padding: 4px 10px; border-radius: 100px;
        border: 1px solid #E8E4DA; background: #F7F5F0; color: #8A8478;
        white-space: nowrap;
    }
    .topbar-role-chip {
        font-size: 11px; font-weight: 700; letter-spacing: 0.04em; text-transform: capitalize;
        padding: 4px 10px; border-radius: 100px;
        background: rgba(24,56,46,0.08); border: 1px solid rgba(24,56,46,0.15);
        color: #18382E;
    }
    .topbar-icon-btn {
        width: 32px; height: 32px; border-radius: 7px; border: 1px solid #E8E4DA;
        background: white; display: flex; align-items: center; justify-content: center;
        cursor: pointer; color: #8A8478; transition: all 0.12s;
        flex-shrink: 0;
    }
    .topbar-icon-btn:hover { background: #F7F5F0; color: #343130; border-color: #D6D2C9; }

    /* ─── Flash Messages ──────────────────────────────────────────────── */
    .flash-success {
        display: flex; align-items: flex-start; gap: 10px;
        background: #F0FDF4; border: 1px solid #A7F3D0;
        border-left: 3px solid #10B981;
        padding: 10px 14px; border-radius: 8px;
        font-size: 13px; color: #065F46;
    }
    .flash-error {
        display: flex; align-items: flex-start; gap: 10px;
        background: #FEF2F2; border: 1px solid #FECACA;
        border-left: 3px solid #EF4444;
        padding: 10px 14px; border-radius: 8px;
        font-size: 13px; color: #991B1B;
    }

    /* ─── Page Layout ─────────────────────────────────────────────────── */
    .page-header {
        display: flex; align-items: flex-start; justify-content: space-between; gap: 16px;
        margin-bottom: 24px;
    }
    .page-title { font-size: 1.25rem; font-weight: 700; color: #1E1C1A; letter-spacing: -0.01em; }
    .page-desc  { font-size: 12.5px; color: #8A8478; margin-top: 3px; }

    /* ─── Cards ───────────────────────────────────────────────────────── */
    .card {
        background: #ffffff;
        border: 1px solid #E8E4DA;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
    }
    .card-header {
        padding: 14px 18px;
        border-bottom: 1px solid #F0EEE8;
        display: flex; align-items: center; justify-content: space-between;
    }
    .card-title { font-size: 12.5px; font-weight: 700; color: #343130; letter-spacing: 0.01em; }
    .card-body  { padding: 18px; }

    /* ─── KPI Cards ───────────────────────────────────────────────────── */
    .kpi {
        background: #ffffff;
        border: 1px solid #E8E4DA;
        border-radius: 10px;
        padding: 16px 18px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        position: relative;
        overflow: hidden;
    }
    .kpi-label  { font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #8A8478; margin-bottom: 10px; }
    .kpi-value  { font-size: 1.625rem; font-weight: 700; color: #1E1C1A; letter-spacing: -0.02em; line-height: 1; }
    .kpi-sub    { font-size: 11.5px; color: #B8B2A6; margin-top: 6px; }
    .kpi-icon   {
        position: absolute; right: 14px; top: 14px;
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        opacity: 0.85;
    }
    .kpi-divider { width: 3px; height: 36px; border-radius: 2px; position: absolute; left: 0; top: 50%; transform: translateY(-50%); }

    /* ─── Toolbar ─────────────────────────────────────────────────────── */
    .toolbar {
        background: white; border: 1px solid #E8E4DA; border-radius: 10px;
        padding: 12px 14px;
        display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
    }
    .toolbar-search {
        display: flex; align-items: center; gap: 8px;
        background: #F7F5F0; border: 1px solid #E8E4DA; border-radius: 7px;
        padding: 7px 12px; flex: 1; min-width: 200px;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .toolbar-search:focus-within { border-color: rgba(198,167,107,0.5); box-shadow: 0 0 0 3px rgba(198,167,107,0.1); background: white; }
    .toolbar-search input { background: none; border: none; outline: none; font-size: 13px; color: #343130; width: 100%; font-family: inherit; }
    .toolbar-search input::placeholder { color: #B8B2A6; }
    .toolbar-select {
        background: white; border: 1px solid #E8E4DA; border-radius: 7px;
        padding: 7px 28px 7px 10px; font-size: 13px; color: #343130;
        font-family: inherit; outline: none; cursor: pointer;
        appearance: none; min-width: 130px;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238A8478' stroke-width='2.5'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 8px center;
        transition: border-color 0.15s;
    }
    .toolbar-select:focus { border-color: rgba(198,167,107,0.5); box-shadow: 0 0 0 3px rgba(198,167,107,0.1); }

    /* ─── Buttons ─────────────────────────────────────────────────────── */
    .btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 7px 14px; border-radius: 7px;
        font-size: 13px; font-weight: 600; font-family: inherit;
        cursor: pointer; transition: all 0.12s; text-decoration: none;
        border: 1px solid transparent; white-space: nowrap;
    }
    .btn-sm { padding: 5px 10px; font-size: 12px; }
    .btn-xs { padding: 3px 8px; font-size: 11px; }

    .btn-primary {
        background: #18382E; color: white; border-color: #18382E;
        box-shadow: 0 1px 2px rgba(0,0,0,0.12);
    }
    .btn-primary:hover { background: #244A3D; border-color: #244A3D; }

    .btn-gold {
        background: #C6A76B; color: white; border-color: #C6A76B;
        box-shadow: 0 1px 2px rgba(198,167,107,0.25);
    }
    .btn-gold:hover { background: #B8965A; border-color: #B8965A; }

    .btn-outline {
        background: white; color: #4D4944; border-color: #DDD8CC;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    .btn-outline:hover { background: #F7F5F0; border-color: #C8C2B6; }

    .btn-ghost { background: transparent; color: #6B6560; border-color: transparent; }
    .btn-ghost:hover { background: #F0EEE8; color: #343130; }

    .btn-danger { background: white; color: #DC2626; border-color: #FECACA; }
    .btn-danger:hover { background: #FEF2F2; border-color: #FCA5A5; }

    .btn-success { background: #059669; color: white; border-color: #059669; }
    .btn-success:hover { background: #047857; }

    /* ─── Table ───────────────────────────────────────────────────────── */
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table thead { background: #FAFAF9; border-bottom: 1px solid #E8E4DA; }
    .data-table th {
        padding: 10px 16px; text-align: left;
        font-size: 10.5px; font-weight: 700; letter-spacing: 0.09em; text-transform: uppercase;
        color: #8A8478; white-space: nowrap;
    }
    .data-table td { padding: 12px 16px; border-bottom: 1px solid #F0EEE8; vertical-align: middle; }
    .data-table tbody tr { transition: background 0.1s; }
    .data-table tbody tr:hover { background: #FAFAF9; }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table .col-id { font-family: 'SF Mono', 'Fira Code', monospace; font-size: 12px; color: #6B6560; }
    .data-table .col-name { font-weight: 600; color: #1E1C1A; }
    .data-table .col-name a { color: #18382E; text-decoration: none; }
    .data-table .col-name a:hover { color: #C6A76B; text-decoration: underline; }
    .data-table .col-num { font-feature-settings: 'tnum'; font-variant-numeric: tabular-nums; }
    .data-table .col-actions { white-space: nowrap; }

    /* ─── Badges ──────────────────────────────────────────────────────── */
    .badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; border-radius: 100px;
        font-size: 11px; font-weight: 700; letter-spacing: 0.03em;
        border: 1px solid transparent; white-space: nowrap;
    }
    .badge-dot { width: 5px; height: 5px; border-radius: 50%; }
    .b-green   { background:#ECFDF5; color:#065F46; border-color:#A7F3D0; }
    .b-emerald { background:#D1FAE5; color:#064E3B; border-color:#6EE7B7; }
    .b-gold    { background:#FFFBEB; color:#78350F; border-color:#FDE68A; }
    .b-amber   { background:#FFF7ED; color:#7C2D12; border-color:#FED7AA; }
    .b-blue    { background:#EFF6FF; color:#1E40AF; border-color:#BFDBFE; }
    .b-purple  { background:#F5F3FF; color:#4C1D95; border-color:#DDD6FE; }
    .b-gray    { background:#F9FAFB; color:#374151; border-color:#E5E7EB; }
    .b-red     { background:#FEF2F2; color:#991B1B; border-color:#FECACA; }
    .b-rose    { background:#FFF1F2; color:#881337; border-color:#FECDD3; }
    .b-forest  { background:#F0FDF4; color:#14532D; border-color:#BBF7D0; }

    /* ─── Forms ───────────────────────────────────────────────────────── */
    .form-group { margin-bottom: 16px; }
    .form-label {
        display: block; font-size: 11.5px; font-weight: 700;
        letter-spacing: 0.06em; text-transform: uppercase; color: #6B6560;
        margin-bottom: 6px;
    }
    .form-input {
        width: 100%; padding: 8px 12px; font-size: 13.5px;
        background: white; border: 1px solid #DDD8CC; border-radius: 7px;
        color: #1E1C1A; font-family: inherit; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .form-input:focus { border-color: #C6A76B; box-shadow: 0 0 0 3px rgba(198,167,107,0.12); }
    .form-input::placeholder { color: #B8B2A6; }
    .form-input.is-error { border-color: #FCA5A5; }
    .form-input.is-error:focus { box-shadow: 0 0 0 3px rgba(239,68,68,0.1); }
    .form-select {
        width: 100%; padding: 8px 32px 8px 12px; font-size: 13.5px;
        background: white; border: 1px solid #DDD8CC; border-radius: 7px;
        color: #1E1C1A; font-family: inherit; outline: none; appearance: none; cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='13' height='13' viewBox='0 0 24 24' fill='none' stroke='%238A8478' stroke-width='2.5'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 10px center;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .form-select:focus { border-color: #C6A76B; box-shadow: 0 0 0 3px rgba(198,167,107,0.12); }
    .form-textarea { resize: vertical; min-height: 80px; line-height: 1.5; }
    .form-hint { font-size: 11.5px; color: #B8B2A6; margin-top: 4px; }
    .form-error { font-size: 11.5px; color: #DC2626; margin-top: 4px; font-weight: 500; }

    /* ─── Status progress bar ─────────────────────────────────────────── */
    .status-pipeline {
        display: flex; align-items: center; padding: 14px 0; overflow: hidden;
    }
    .pipeline-step {
        display: flex; flex-direction: column; align-items: center; gap: 5px; flex: 1; position: relative;
    }
    .pipeline-step:not(:last-child)::after {
        content: ''; position: absolute; top: 12px; left: calc(50% + 14px);
        width: calc(100% - 28px); height: 1.5px; background: #E8E4DA;
    }
    .pipeline-step.done::after  { background: #18382E; }
    .pipeline-dot {
        width: 24px; height: 24px; border-radius: 50%; z-index: 1;
        display: flex; align-items: center; justify-content: center;
        font-size: 10px; font-weight: 700;
        border: 2px solid #E8E4DA; background: white; color: #B8B2A6;
    }
    .pipeline-step.done  .pipeline-dot { background: #18382E; border-color: #18382E; color: white; }
    .pipeline-step.active .pipeline-dot { background: white; border-color: #C6A76B; color: #C6A76B; box-shadow: 0 0 0 3px rgba(198,167,107,0.15); }
    .pipeline-label { font-size: 10.5px; font-weight: 600; color: #B8B2A6; text-align: center; white-space: nowrap; }
    .pipeline-step.done   .pipeline-label { color: #18382E; }
    .pipeline-step.active .pipeline-label { color: #C6A76B; }

    /* ─── Credit bar ──────────────────────────────────────────────────── */
    .credit-bar { height: 6px; background: #F0EEE8; border-radius: 100px; overflow: hidden; }
    .credit-fill { height: 100%; border-radius: 100px; transition: width 0.5s ease; }

    /* ─── Empty State ─────────────────────────────────────────────────── */
    .empty-state {
        padding: 48px 24px; text-align: center;
    }
    .empty-icon {
        width: 48px; height: 48px; border-radius: 12px;
        background: #F0EEE8; display: flex; align-items: center; justify-content: center;
        margin: 0 auto 14px; color: #B8B2A6;
    }
    .empty-title { font-size: 14px; font-weight: 700; color: #4D4944; margin-bottom: 5px; }
    .empty-text  { font-size: 12.5px; color: #8A8478; max-width: 320px; margin: 0 auto 16px; line-height: 1.6; }

    /* ─── Utilities ───────────────────────────────────────────────────── */
    .text-mono { font-family: 'SF Mono', 'Fira Code', 'Courier New', monospace; }
    .divider   { border: none; border-top: 1px solid #F0EEE8; margin: 16px 0; }
    .section-title { font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #8A8478; margin-bottom: 12px; }
    </style>

    @stack('head')
</head>

<body class="h-full overflow-hidden">
<div id="sidebar-backdrop" onclick="toggleSidebar(false)"></div>
<div style="display:flex;height:100vh;overflow:hidden">

    {{-- ═══ SIDEBAR ═══════════════════════════════════════════════════════ --}}
    <aside id="sidebar">

        {{-- Brand --}}
        <div class="sidebar-brand">
            <div style="display:flex;align-items:center;justify-content:space-between">
                <a href="{{ route('dashboard') }}" style="display:flex;align-items:center;gap:12px;text-decoration:none">
                    <img src="{{ asset('images/logo-icon.jpg') }}" alt="Maison Aster" style="width:38px;height:38px;border-radius:8px;object-fit:cover;border:1px solid rgba(198,167,107,0.4);box-shadow:0 2px 8px rgba(198,167,107,0.35);flex-shrink:0">
                    <div>
                        <div class="sidebar-brand-name">MAISON ASTER</div>
                        <div class="sidebar-brand-sub">Fine Dining ERP</div>
                    </div>
                </a>
                <button type="button" onclick="toggleSidebar(false)" class="lg:hidden text-white/60 hover:text-white p-1 rounded-lg transition-colors" title="Tutup menu">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="sidebar-nav">
        @php
            $role = strtolower(auth()->user()->role ?? '');
            $roleLabel = match($role) {
                'owner'       => 'Owner / GM',
                'host'        => 'Host & Reservasi',
                'server'      => 'Pelayan / Server',
                'warehouse'   => 'Gudang & Stok',
                'finance'     => 'Finance Manager',
                'procurement' => 'Purchasing Lead',
                'cashier'     => 'Kasir Restoran',
                default       => ucfirst(auth()->user()->role ?? 'Pengguna')
            };
        @endphp

            <a href="{{ route('dashboard') }}"
               class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                Dashboard
            </a>

            {{-- ── CRM: owner, finance, host ── --}}
            @if(in_array($role, ['owner','finance','host']))
            <div class="nav-section-label">CRM & Prospek</div>
            <a href="{{ route('crm.leads.index') }}"
               class="nav-item {{ request()->routeIs('crm.leads.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                Prospek (Lead)
                @php $lc = \App\Models\CrmLead::where('is_converted',false)->count(); @endphp
                @if($lc > 0)<span class="nav-badge">{{ $lc }}</span>@endif
            </a>
            <a href="{{ route('crm.opportunities.index') }}"
               class="nav-item {{ request()->routeIs('crm.opportunities.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                Peluang (Opportunity)
            </a>
            <a href="{{ route('crm.pipeline') }}"
               class="nav-item {{ request()->routeIs('crm.pipeline') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                Pipeline
            </a>
            @endif

            {{-- ── Penjualan & Kontak: owner, finance, host (+ kontak untuk cashier/procurement) ── --}}
            @if(in_array($role, ['owner','finance','host','cashier','procurement']))
            <div class="nav-section-label">Penjualan &amp; Relasi</div>
            <a href="{{ route('contacts.index') }}"
               class="nav-item {{ request()->routeIs('contacts.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Kontak &amp; {{ $role === 'procurement' ? 'Vendor' : 'Pelanggan' }}
            </a>
            @if(in_array($role, ['owner','finance','host']))
            <a href="{{ route('sales.quotations.index') }}"
               class="nav-item {{ request()->routeIs('sales.quotations.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Surat Penawaran
                @php $qc = \App\Models\Quotation::where('status','Draft')->count(); @endphp
                @if($qc > 0)<span class="nav-badge">{{ $qc }}</span>@endif
            </a>
            <a href="{{ route('sales.orders.index') }}"
               class="nav-item {{ request()->routeIs('sales.orders.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Pesanan Penjualan (SO)
            </a>
            @endif
            @endif

            {{-- Diskon & Kredit: owner, finance saja ── --}}
            @if(in_array($role, ['owner','finance']))
            <a href="{{ route('sales.discounts.index') }}"
               class="nav-item {{ request()->routeIs('sales.discounts.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                Persetujuan Diskon
                @php $dc = \App\Models\DiscountApproval::where('status','Pending')->count(); @endphp
                @if($dc > 0)<span class="nav-badge urgent">{{ $dc }}</span>@endif
            </a>
            <a href="{{ route('sales.credit.index') }}"
               class="nav-item {{ request()->routeIs('sales.credit.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Kontrol Kredit
            </a>
            <a href="{{ route('sales.reports.index') }}"
               class="nav-item {{ request()->routeIs('sales.reports.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Laporan Penjualan
            </a>
            @endif

            {{-- ── Restoran & Menu: semua role operasional restoran (+ semua staf lihat menu) ── --}}
            <div class="nav-section-label">Restoran &amp; Menu</div>
            @if(in_array($role, ['owner','host']))
            <a href="{{ route('bookings.index') }}"
               class="nav-item {{ request()->routeIs('bookings.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Reservasi Meja
            </a>
            @endif
            @if(in_array($role, ['owner','host','server','cashier']))
            <a href="{{ route('table-orders.index') }}"
               class="nav-item {{ request()->routeIs('table-orders.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
                Pesanan Meja / POS
            </a>
            @endif
            <a href="{{ route('products.index') }}"
               class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                Menu &amp; Produk
            </a>

            {{-- ── Rantai Pasok: warehouse, procurement (+ finance lihat PO) ── --}}
            @if(in_array($role, ['owner','warehouse','procurement','finance']))
            <div class="nav-section-label">Rantai Pasok &amp; Gudang</div>
            @if(in_array($role, ['owner','warehouse']))
            <a href="{{ route('inventory.index') }}"
               class="nav-item {{ request()->routeIs('inventory.index') || request()->routeIs('inventory.transfer.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                Persediaan Stok
            </a>
            <a href="{{ route('inventory.deliveries') }}"
               class="nav-item {{ request()->routeIs('inventory.deliveries') || request()->routeIs('inventory.delivery.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Surat Jalan (DO)
            </a>
            @endif
            @if(in_array($role, ['owner','procurement','warehouse','finance']))
            <a href="{{ route('purchase.index') }}"
               class="nav-item {{ request()->routeIs('purchase.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                {{ $role === 'warehouse' ? 'Penerimaan PO' : 'Pembelian (PO)' }}
            </a>
            @endif
            @endif

            {{-- ── Keuangan & Retur: finance, cashier (+ warehouse lihat retur) ── --}}
            @if(in_array($role, ['owner','finance','cashier','warehouse']))
            <div class="nav-section-label">Keuangan &amp; Akuntansi</div>
            @if(in_array($role, ['owner','finance','cashier']))
            <a href="{{ route('invoices.index') }}"
               class="nav-item {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Faktur &amp; Pembayaran
            </a>
            @endif
            @if(in_array($role, ['owner','finance','warehouse']))
            <a href="{{ route('returns.index') }}"
               class="nav-item {{ request()->routeIs('returns.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                Retur Barang
            </a>
            @endif
            @if(in_array($role, ['owner','finance']))
            <a href="{{ route('accounting.index') }}"
               class="nav-item {{ request()->routeIs('accounting.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Jurnal &amp; Buku Besar
            </a>
            @endif
            @endif

            {{-- ── Karyawan: semua role bisa lihat ── --}}
            <div class="nav-section-label">Manajemen</div>
            <a href="{{ route('employees.index') }}"
               class="nav-item {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Karyawan
            </a>
            @if($role === 'owner')
            <a href="{{ route('activity-logs.index') }}"
               class="nav-item {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Log Aktivitas
            </a>
            @endif

        </nav>

        {{-- Footer --}}
        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name,0,2)) }}</div>
                <div style="flex:1;min-width:0">
                    <div class="user-name">{{ auth()->user()->name }}</div>
                    {{-- roleLabel sudah dideklarasikan di nav --}}
                    <div class="user-role">{{ $roleLabel }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}" style="flex-shrink:0">
                    @csrf
                    <button type="submit" title="Keluar dari sistem" style="display:flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:6px;background:transparent;border:none;cursor:pointer;color:rgba(255,255,255,0.35);transition:all 0.12s" onmouseover="this.style.background='rgba(255,255,255,0.1)';this.style.color='rgba(255,255,255,0.8)'" onmouseout="this.style.background='transparent';this.style.color='rgba(255,255,255,0.35)'">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    </button>
                </form>
            </div>
        </div>

    </aside>

    {{-- ═══ MAIN ════════════════════════════════════════════════════════════ --}}
    <div style="flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0">

        {{-- Topbar --}}
        <div id="topbar">
            <button type="button" onclick="toggleSidebar(true)" class="lg:hidden topbar-icon-btn" style="margin-right:6px" title="Buka menu navigasi">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="breadcrumb">
                <span class="breadcrumb-item" style="color:#343130;font-weight:600">Maison Aster</span>
                @hasSection('breadcrumb')
                    <span class="breadcrumb-sep">›</span>
                    @yield('breadcrumb')
                @endif
            </div>
            <div class="topbar-search hidden sm:flex" style="width:200px">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;color:#B8B2A6"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="text" placeholder="Cari data…">
            </div>
            <div class="topbar-divider hidden sm:block"></div>
            <div class="topbar-chip hidden md:flex" style="font-size:11px">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                Jakarta Selatan
            </div>
            <div class="topbar-role-chip hidden sm:block">{{ $roleLabel }}</div>
        </div>

        {{-- Flash --}}
        @if(session('success'))
        <div style="padding:10px 20px 0">
            <div class="flash-success">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="flex-shrink:0;margin-top:1px"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#6EE7B7;font-size:16px;line-height:1;padding:0">×</button>
            </div>
        </div>
        @endif
        @if(session('error'))
        <div style="padding:10px 20px 0">
            <div class="flash-error">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>{{ session('error') }}</span>
                <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#FCA5A5;font-size:16px;line-height:1;padding:0">×</button>
            </div>
        </div>
        @endif

        {{-- Content --}}
        <main style="flex:1;overflow-y:auto;padding:1.5rem 1rem" class="sm:p-6 lg:p-8">
            <div style="width:100%;max-width:1720px;margin-left:auto;margin-right:auto">
                @yield('content')
            </div>
        </main>

    </div>
</div>

<script>
function toggleSidebar(open) {
    const sb = document.getElementById('sidebar');
    const bd = document.getElementById('sidebar-backdrop');
    if (!sb || !bd) return;
    if (open) {
        sb.classList.add('open');
        bd.classList.add('open');
    } else {
        sb.classList.remove('open');
        bd.classList.remove('open');
    }
}
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
@stack('scripts')
</body>
</html>
