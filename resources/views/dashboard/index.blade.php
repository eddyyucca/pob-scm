@extends('layouts.app')
@section('title', 'Dashboard POB & Manpower')
@section('content')
<div class="d-flex" style="min-height:100vh;">

{{-- ===== SIDEBAR ===== --}}
<nav class="d-flex flex-column p-3" style="width:220px;min-height:100vh;background:#1a3c5e;color:#fff;flex-shrink:0;">
    <div class="mb-4 mt-2 text-center">
        <div style="font-size:1.05rem;font-weight:700;letter-spacing:1px;">&#x26CF; SCM Nickel</div>
        <div style="font-size:.72rem;opacity:.55;">POB & Manpower System</div>
    </div>
    @php
        $navItems = [
            ['route'=>'dashboard','icon'=>'bi-speedometer2','label'=>'Dashboard'],
            ['route'=>'dashboard.import','icon'=>'bi-upload','label'=>'Import Excel'],
            ['route'=>'dashboard.export','icon'=>'bi-download','label'=>'Export CSV','params'=>['from'=>now()->startOfMonth()->toDateString(),'to'=>now()->toDateString()]],
        ];
    @endphp
    @foreach($navItems as $n)
    <a href="{{ isset($n['params']) ? route($n['route'], $n['params']) : route($n['route']) }}"
       class="nav-link text-white py-2 px-3 rounded mb-1"
       style="{{ request()->routeIs(explode('.',$n['route'])[0].'*') && !isset($n['params']) ? 'background:rgba(255,255,255,.15);' : 'opacity:.7;' }}">
        <i class="bi {{ $n['icon'] }} me-2"></i>{{ $n['label'] }}
    </a>
    @endforeach
    <div class="mt-auto pt-3 border-top border-secondary">
        <div style="font-size:.75rem;opacity:.55;margin-bottom:6px;">{{ auth()->user()->name ?? 'Admin' }}</div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-sm btn-outline-light w-100">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </button>
        </form>
    </div>
</nav>

{{-- ===== MAIN ===== --}}
<main class="flex-grow-1 p-4" style="background:#f0f4f8;overflow-x:hidden;">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">Dashboard POB & Manpower</h4>
            <small class="text-muted">Data terakhir: {{ $latestDate ? \Carbon\Carbon::parse($latestDate)->format('d M Y') : '-' }}
                &nbsp;|&nbsp; Total entri: {{ number_format($totalEntries) }}</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('dashboard') }}?view=daily&date={{ $date }}" class="btn btn-sm {{ $viewType=='daily' ? 'btn-primary' : 'btn-outline-secondary' }}">Harian</a>
            <a href="{{ route('dashboard') }}?view=weekly&week={{ $week }}" class="btn btn-sm {{ $viewType=='weekly' ? 'btn-primary' : 'btn-outline-secondary' }}">Mingguan</a>
            <a href="{{ route('dashboard') }}?view=monthly&month={{ $month }}" class="btn btn-sm {{ $viewType=='monthly' ? 'btn-primary' : 'btn-outline-secondary' }}">Bulanan</a>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="card border-0 shadow-sm mb-4 p-3" style="border-radius:10px;">
        <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-items-center gap-3 flex-wrap">
            <input type="hidden" name="view" value="{{ $viewType }}">
            @if($viewType === 'daily')
                <label class="mb-0 fw-semibold small text-muted">Pilih Tanggal:</label>
                <input type="date" name="date" class="form-control form-control-sm" style="width:auto;" value="{{ $date }}" max="{{ now()->toDateString() }}">
            @elseif($viewType === 'weekly')
                <label class="mb-0 fw-semibold small text-muted">Pilih Minggu:</label>
                <input type="week" name="week" class="form-control form-control-sm" style="width:auto;" value="{{ $week }}">
            @else
                <label class="mb-0 fw-semibold small text-muted">Pilih Bulan:</label>
                <input type="month" name="month" class="form-control form-control-sm" style="width:auto;" value="{{ $month }}">
            @endif
            <button class="btn btn-sm btn-primary">Tampilkan</button>
            <a href="{{ route('dashboard.export') }}?from={{ $date }}&to={{ $date }}" class="btn btn-sm btn-outline-success ms-auto">
                <i class="bi bi-download me-1"></i>Export CSV
            </a>
        </form>
    </div>

    {{-- ===== STAT CARDS ===== --}}
    @php
        $totals = $viewType === 'daily' ? $dailyTotals : ($viewType === 'weekly' ? $weeklyTotals : $monthlyTotals);
        $cards = [
            ['label'=>'Total POB','val'=>number_format($totals['pob']),'icon'=>'bi-people-fill','color'=>'#0d6efd','bg'=>'rgba(13,110,253,.1)'],
            ['label'=>'Total Manpower','val'=>number_format($totals['mp']),'icon'=>'bi-person-workspace','color'=>'#198754','bg'=>'rgba(25,135,84,.1)'],
            ['label'=>'Rasio POB/MP','val'=>($totals['ratio'] ?? 0).'%','icon'=>'bi-percent','color'=>'#fd7e14','bg'=>'rgba(253,126,20,.1)'],
            ['label'=>'Perusahaan Lapor','val'=>$totals['reporters'].' / '.$totalCompanies,'icon'=>'bi-building','color'=>'#6f42c1','bg'=>'rgba(111,66,193,.1)'],
        ];
    @endphp
    <div class="row g-3 mb-4">
        @foreach($cards as $c)
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:{{ $c['bg'] }};">
                        <i class="bi {{ $c['icon'] }}" style="font-size:1.3rem;color:{{ $c['color'] }};"></i>
                    </div>
                    <div>
                        <div style="font-size:.75rem;color:#64748b;">{{ $c['label'] }}</div>
                        <div style="font-size:1.4rem;font-weight:700;color:{{ $c['color'] }};line-height:1.2;">{{ $c['val'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ===== CHART ROW 1: Trend Line + Donut ===== --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Trend POB & Manpower</h6>
                    <div class="btn-group btn-group-sm" id="trendToggle">
                        <button class="btn btn-primary active" onclick="showTrend('daily',this)">30 Hari</button>
                        <button class="btn btn-outline-secondary" onclick="showTrend('weekly',this)">12 Minggu</button>
                        <button class="btn btn-outline-secondary" onclick="showTrend('monthly',this)">12 Bulan</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="110"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0">Distribusi POB per Perusahaan</h6>
                    <small class="text-muted">
                        {{ $viewType === 'daily' ? \Carbon\Carbon::parse($date)->format('d M Y') : ($viewType === 'weekly' ? 'Minggu '.$week : $month) }}
                    </small>
                </div>
                <div class="card-body">
                    <canvas id="donutChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== CHART ROW 2: Bar chart perusahaan top ===== --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0">Top 10 POB per Perusahaan</h6>
                    <small class="text-muted">Periode {{ $viewType }}</small>
                </div>
                <div class="card-body">
                    <canvas id="barCompChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0">Perbandingan POB vs Manpower</h6>
                    <small class="text-muted">Top 10 perusahaan</small>
                </div>
                <div class="card-body">
                    <canvas id="barCompareChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== TABEL DETAIL ===== --}}
    @php
        $tableData = $viewType === 'daily' ? $dailyData : ($viewType === 'weekly' ? $weeklyData : $monthlyData);
        $totalPob = $viewType === 'daily' ? $totals['pob'] : ($viewType === 'weekly' ? $weeklyTotals['pob'] : $monthlyTotals['pob']);
    @endphp
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3">
            <h6 class="fw-bold mb-0">Detail per Perusahaan</h6>
            <span class="badge bg-primary">{{ $tableData->count() }} perusahaan</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle" style="font-size:.85rem;">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th class="px-3 py-2">#</th>
                            <th class="py-2">Perusahaan</th>
                            <th class="py-2 text-end">POB</th>
                            <th class="py-2 text-end">Manpower</th>
                            <th class="py-2 text-end">Rasio</th>
                            <th class="py-2">Distribusi</th>
                            @if($viewType === 'daily')
                            <th class="py-2">Dilaporkan Oleh</th>
                            <th class="py-2">Kontak</th>
                            @else
                            <th class="py-2 text-center">Hari Lapor</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tableData as $i => $row)
                        @php
                            $pob  = $viewType === 'daily' ? $row->total_pob : ($row->pob ?? 0);
                            $mp   = $viewType === 'daily' ? $row->total_manpower : ($row->mp ?? 0);
                            $ratio = $mp > 0 ? round(($pob / $mp) * 100, 1) : 0;
                            $pct   = $totalPob > 0 ? round(($pob / $totalPob) * 100, 1) : 0;
                            $barColor = $pct > 30 ? '#0d6efd' : ($pct > 15 ? '#198754' : '#6c757d');
                        @endphp
                        <tr>
                            <td class="px-3 text-muted">{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $row->company->name ?? '-' }}</td>
                            <td class="text-end text-primary fw-bold">{{ number_format($pob) }}</td>
                            <td class="text-end text-success">{{ number_format($mp) }}</td>
                            <td class="text-end">
                                <span class="badge" style="background:{{ $ratio >= 80 ? '#198754' : ($ratio >= 50 ? '#fd7e14' : '#6c757d') }};">
                                    {{ $ratio }}%
                                </span>
                            </td>
                            <td style="min-width:100px;">
                                <div class="d-flex align-items-center gap-1">
                                    <div class="flex-grow-1 rounded" style="height:6px;background:#e2e8f0;">
                                        <div class="rounded" style="height:6px;width:{{ $pct }}%;background:{{ $barColor }};transition:width .5s;"></div>
                                    </div>
                                    <small class="text-muted" style="white-space:nowrap;">{{ $pct }}%</small>
                                </div>
                            </td>
                            @if($viewType === 'daily')
                            <td class="text-muted small">{{ $row->informed_by ?? '-' }}</td>
                            <td class="text-muted small">{{ $row->contact_wa ?? '-' }}</td>
                            @else
                            <td class="text-center text-muted">{{ $row->days ?? 0 }} hari</td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                                Belum ada data untuk periode ini
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($tableData->count() > 0)
                    <tfoot style="background:#f8fafc;font-weight:700;">
                        <tr>
                            <td colspan="2" class="px-3 py-2">TOTAL</td>
                            <td class="text-end text-primary">{{ number_format($totals['pob']) }}</td>
                            <td class="text-end text-success">{{ number_format($totals['mp']) }}</td>
                            <td class="text-end">
                                <span class="badge bg-primary">{{ $totals['ratio'] ?? 0 }}%</span>
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

</main>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
@php
    // === Trend data ===
    $tDaily   = $trendData->map(fn($r) => ['date' => \Carbon\Carbon::parse($r->date)->format('d M'), 'pob' => (int)$r->pob, 'mp' => (int)$r->mp]);
    $tWeekly  = $weeklyTrend->map(fn($r) => ['date' => \Carbon\Carbon::parse($r->week_start)->format('d M'), 'pob' => (int)$r->pob, 'mp' => (int)$r->mp]);
    $tMonthly = $monthlyTrend->map(fn($r) => ['date' => \Carbon\Carbon::parse($r->period.'-01')->format('M Y'), 'pob' => (int)$r->pob, 'mp' => (int)$r->mp]);

    // === Current view table data for charts ===
    $chartData = $viewType === 'daily' ? $dailyData : ($viewType === 'weekly' ? $weeklyData : $monthlyData);
    $top10 = $chartData->sortByDesc(fn($r) => $viewType === 'daily' ? $r->total_pob : ($r->pob ?? 0))->take(10);
    $chartLabels = $top10->map(fn($r) => $r->company->name ?? 'N/A')->values()->toArray();
    $chartPob    = $top10->map(fn($r) => (int)($viewType === 'daily' ? $r->total_pob : ($r->pob ?? 0)))->values()->toArray();
    $chartMp     = $top10->map(fn($r) => (int)($viewType === 'daily' ? $r->total_manpower : ($r->mp ?? 0)))->values()->toArray();

    // === Donut: semua perusahaan ===
    $donutLabels = $chartData->map(fn($r) => $r->company->name ?? 'N/A')->values()->toArray();
    $donutPob    = $chartData->map(fn($r) => (int)($viewType === 'daily' ? $r->total_pob : ($r->pob ?? 0)))->values()->toArray();
@endphp

const TREND_DATA = {
    daily:   {!! json_encode($tDaily->values()) !!},
    weekly:  {!! json_encode($tWeekly->values()) !!},
    monthly: {!! json_encode($tMonthly->values()) !!},
};

const PALETTE = [
    '#0d6efd','#198754','#fd7e14','#6f42c1','#dc3545',
    '#20c997','#ffc107','#0dcaf0','#6c757d','#343a40',
    '#e83e8c','#17a2b8','#28a745','#ff6384','#36a2eb',
    '#ffcd56','#4bc0c0','#9966ff','#ff9f40','#c9cbcf'
];

// =============================================
// TREND CHART
// =============================================
const trendCtx = document.getElementById('trendChart').getContext('2d');
let trendChart = new Chart(trendCtx, makeTrendConfig(TREND_DATA.daily));

function makeTrendConfig(data) {
    return {
        type: 'line',
        data: {
            labels: data.map(d => d.date),
            datasets: [
                {
                    label: 'POB',
                    data: data.map(d => d.pob),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,.08)',
                    tension: 0.4, fill: true, pointRadius: 3, borderWidth: 2,
                },
                {
                    label: 'Manpower',
                    data: data.map(d => d.mp),
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25,135,84,.08)',
                    tension: 0.4, fill: true, pointRadius: 3, borderWidth: 2,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: false, grid: { color: 'rgba(0,0,0,.05)' } },
                x: { grid: { display: false } }
            }
        }
    };
}

function showTrend(type, btn) {
    document.querySelectorAll('#trendToggle .btn').forEach(b => {
        b.classList.remove('btn-primary','active');
        b.classList.add('btn-outline-secondary');
    });
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-primary','active');
    trendChart.data.labels   = TREND_DATA[type].map(d => d.date);
    trendChart.data.datasets[0].data = TREND_DATA[type].map(d => d.pob);
    trendChart.data.datasets[1].data = TREND_DATA[type].map(d => d.mp);
    trendChart.update();
}

// =============================================
// DONUT CHART
// =============================================
const donutLabels = {!! json_encode($donutLabels) !!};
const donutPob    = {!! json_encode($donutPob) !!};

new Chart(document.getElementById('donutChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: donutLabels,
        datasets: [{
            data: donutPob,
            backgroundColor: PALETTE.slice(0, donutLabels.length),
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 10 }, boxWidth: 12, padding: 8 } },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.label}: ${ctx.parsed.toLocaleString()} (${donutPob.reduce((a,b)=>a+b,0) > 0 ? ((ctx.parsed/donutPob.reduce((a,b)=>a+b,0))*100).toFixed(1) : 0}%)`
                }
            }
        }
    }
});

// =============================================
// BAR: Top 10 POB per perusahaan
// =============================================
const barLabels  = {!! json_encode($chartLabels) !!};
const barPob     = {!! json_encode($chartPob) !!};
const barMp      = {!! json_encode($chartMp) !!};

new Chart(document.getElementById('barCompChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: barLabels.map(l => l.length > 20 ? l.substring(0,18)+'...' : l),
        datasets: [{
            label: 'POB',
            data: barPob,
            backgroundColor: PALETTE.slice(0, barLabels.length),
            borderRadius: 5,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' } },
            y: { grid: { display: false }, ticks: { font: { size: 10 } } }
        }
    }
});

// =============================================
// BAR: POB vs Manpower comparison
// =============================================
new Chart(document.getElementById('barCompareChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: barLabels.map(l => l.length > 15 ? l.substring(0,13)+'...' : l),
        datasets: [
            { label: 'POB', data: barPob, backgroundColor: 'rgba(13,110,253,.75)', borderRadius: 4 },
            { label: 'Manpower', data: barMp, backgroundColor: 'rgba(25,135,84,.75)', borderRadius: 4 },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10 } } },
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' } }
        }
    }
});
</script>
@endpush