<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title','POB System') — PT SCM</title>
    <link rel="icon" type="image/x-icon" href="https://tms.scmnickel.com/assets/v2/img/branding/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --navy:   #1a3c5e;
            --blue:   #2563eb;
            --green:  #16a34a;
            --orange: #ea580c;
            --purple: #7c3aed;
            --teal:   #0891b2;
            --sb-w:   220px;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f0f4f8; margin:0; padding:0; }

        /* ── Layout utama ── */
        .app-wrap   { display:flex; min-height:100vh; }
        .app-main   { flex:1; min-width:0; transition:margin-left .25s; }

        /* ── Sidebar ── */
        .app-sidebar {
            width: var(--sb-w);
            min-height: 100vh;
            background: var(--navy);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            padding: 14px 10px;
            position: fixed;
            top: 0; left: 0;
            z-index: 1040;
            transition: transform .25s ease;
            overflow-y: auto;
        }
        .app-sidebar.collapsed { transform: translateX(calc(-1 * var(--sb-w))); }

        /* Overlay untuk mobile */
        .sb-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 1039;
        }
        .sb-overlay.show { display: block; }

        /* Main content offset saat sidebar tampil */
        @media (min-width: 992px) {
            .app-main { margin-left: var(--sb-w); }
            .topbar-toggle { display: none !important; }
        }
        @media (max-width: 991.98px) {
            .app-sidebar { transform: translateX(calc(-1 * var(--sb-w))); }
            .app-sidebar.open { transform: translateX(0); }
            .app-main { margin-left: 0; }
        }

        /* ── Topbar mobile ── */
        .app-topbar {
            display: none;
            align-items: center;
            gap: 10px;
            background: var(--navy);
            padding: 10px 16px;
            color: #fff;
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        @media (max-width: 991.98px) { .app-topbar { display: flex; } }

        /* ── Sidebar nav ── */
        .sb-logo { text-align:center; padding:8px 0 20px; color:#fff; }
        .sb-logo h6 { font-size:1rem; font-weight:700; margin:0; }
        .sb-logo small { font-size:.68rem; opacity:.4; }
        .sb-nav a {
            display: flex; align-items: center; gap: 9px;
            padding: 9px 12px; border-radius: 8px;
            color: rgba(255,255,255,.7); text-decoration: none;
            font-size: .84rem; margin-bottom: 2px;
            transition: all .15s;
        }
        .sb-nav a:hover, .sb-nav a.on {
            background: rgba(255,255,255,.15); color: #fff;
        }
        .sb-nav a.on { font-weight: 600; }
        .sb-footer { margin-top:auto; padding-top:14px; border-top:1px solid rgba(255,255,255,.1); }
        .sb-footer small { font-size:.7rem; color:rgba(255,255,255,.35); padding:0 12px 6px; display:block; }

        /* ── Cards ── */
        .kard { background:#fff; border-radius:12px; box-shadow:0 1px 8px rgba(0,0,0,.07); }
        .kard-hdr { padding:13px 16px 10px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:6px; }
        .kard-title { font-size:.8rem; font-weight:700; color:#374151; }
        .pill-row { display:flex; background:#f1f5f9; border-radius:20px; padding:2px; flex-wrap:wrap; }
        .pill-row a { border-radius:18px; padding:3px 13px; font-size:.75rem; color:#64748b; text-decoration:none; transition:all .15s; white-space:nowrap; }
        .pill-row a.on { background:#2563eb; color:#fff; }

        /* ── Mobile table scroll ── */
        .table-resp { overflow-x: auto; -webkit-overflow-scrolling: touch; }

        /* ── Padding main ── */
        .main-pad { padding: 20px 20px; }
        @media (max-width: 767.98px) { .main-pad { padding: 14px 12px; } }
    </style>
    @stack('styles')
</head>
<body>
<div class="app-wrap">

    {{-- Overlay untuk mobile --}}
    <div class="sb-overlay" id="sbOverlay" onclick="closeSidebar()"></div>

    {{-- Sidebar --}}
    <aside class="app-sidebar" id="appSidebar">
        <div class="sb-logo">
            <h6>SCM Nickel</h6>
            <small>POB & Manpower System</small>
        </div>
        <nav class="sb-nav">
            @yield('sidebar-nav')
        </nav>
        <div class="sb-footer">
            <small>{{ auth()->check() ? auth()->user()->name : '' }}</small>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button style="width:100%;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.75);border-radius:8px;padding:7px;font-size:.8rem;cursor:pointer;">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="app-main">

        {{-- Topbar mobile --}}
        <div class="app-topbar">
            <button class="topbar-toggle btn btn-sm" style="background:rgba(255,255,255,.12);border:none;color:#fff;padding:6px 10px;" onclick="openSidebar()">
                <i class="bi bi-list" style="font-size:1.2rem;"></i>
            </button>
            <span style="font-weight:700;font-size:.95rem;">@yield('title','POB System')</span>
        </div>

        <div class="main-pad">
            @yield('content')
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openSidebar() {
    document.getElementById('appSidebar').classList.add('open');
    document.getElementById('sbOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('appSidebar').classList.remove('open');
    document.getElementById('sbOverlay').classList.remove('show');
    document.body.style.overflow = '';
}
// Tutup sidebar saat klik link nav (mobile)
document.querySelectorAll('.sb-nav a').forEach(a =>
    a.addEventListener('click', () => { if(window.innerWidth < 992) closeSidebar(); })
);
</script>
@stack('scripts')
</body>
</html>
