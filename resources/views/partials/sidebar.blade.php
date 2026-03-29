<nav style="width:220px;min-height:100vh;background:#1a3c5e;flex-shrink:0;display:flex;flex-direction:column;padding:14px 10px;">
    <div style="text-align:center;padding:8px 0 20px;color:#fff;">
        <div style="font-size:1rem;font-weight:700;">⛏ SCM Nickel</div>
        <div style="font-size:.68rem;opacity:.4;">POB & Manpower System</div>
    </div>
    @php
    $nav = [
        ['r'=>'dashboard',         'i'=>'bi-speedometer2',    'l'=>'Dashboard'],
        ['r'=>'pob-entries.index', 'i'=>'bi-journal-text',    'l'=>'Laporan POB'],
        ['r'=>'employees.index',   'i'=>'bi-people',          'l'=>'Data Karyawan'],
        ['r'=>'companies.index',   'i'=>'bi-building',        'l'=>'Perusahaan'],
        ['r'=>'notifications.index','i'=>'bi-bell',           'l'=>'Notifikasi WA'],
        ['r'=>'report.index',       'i'=>'bi-bar-chart-line', 'l'=>'Laporan'],
        ['r'=>'users.index',       'i'=>'bi-person-gear',     'l'=>'Manajemen User'],
        ['r'=>'dashboard.import',  'i'=>'bi-upload',          'l'=>'Import Excel'],
    ];
    @endphp
    @foreach($nav as $n)
    <a href="{{ route($n['r']) }}"
       style="display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:8px;color:rgba(255,255,255,.7);text-decoration:none;font-size:.84rem;margin-bottom:2px;transition:all .15s;
       {{ request()->routeIs($n['r'].'*') ? 'background:rgba(255,255,255,.15);color:#fff;' : '' }}">
        <i class="bi {{ $n['i'] }}"></i>{{ $n['l'] }}
    </a>
    @endforeach
    <div style="margin-top:auto;padding-top:14px;border-top:1px solid rgba(255,255,255,.1);">
        <div style="font-size:.7rem;color:rgba(255,255,255,.35);padding:0 12px 6px;">{{ auth()->user()->name }}</div>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button style="width:100%;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.75);border-radius:8px;padding:7px;font-size:.8rem;cursor:pointer;">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </button>
        </form>
    </div>
</nav>