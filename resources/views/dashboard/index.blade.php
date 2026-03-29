@extends('layouts.app')
@section('title','Dashboard POB')

@push('styles')
<style>
:root{--blue:#2563eb;--green:#16a34a;--orange:#ea580c;--purple:#7c3aed;--teal:#0891b2;}
/* Extra responsive overrides khusus dashboard */
@media (max-width:575.98px) {
    .kard-title { font-size:.75rem; }
    .card-body { padding: .75rem !important; }
    .kard-hdr { padding: 10px 12px 8px; }
    #trendBtns { gap: 2px !important; }
    #trendBtns button { font-size:.68rem !important; padding: 2px 8px !important; }
}
</style>
@endpush

@section('sidebar-nav')
@include('partials.sidebar')
@endsection

@section('content')
{{-- SIDEBAR --}}
{{-- MAIN --}}
{{-- Topbar --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0" style="color:#1e293b;">Dashboard POB & Manpower</h5>
            <small class="text-muted">Update terakhir: <b>{{ $latestDate ? \Carbon\Carbon::parse($latestDate)->format('d M Y') : '-' }}</b>
            &middot; {{ number_format($totalEntries) }} total entri</small>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <div class="pill-row">
                <a href="{{ route('dashboard') }}?view=daily&date={{ $date }}" class="{{ $viewType==='daily'?'on':'' }}">Harian</a>
                <a href="{{ route('dashboard') }}?view=weekly&week={{ $week }}" class="{{ $viewType==='weekly'?'on':'' }}">Mingguan</a>
                <a href="{{ route('dashboard') }}?view=monthly&month={{ $month }}" class="{{ $viewType==='monthly'?'on':'' }}">Bulanan</a>
            </div>
            <a href="{{ route('dashboard.export') }}?from={{ $date }}&to={{ $date }}" class="btn btn-sm btn-outline-success" style="border-radius:20px;font-size:.78rem;">
                <i class="bi bi-download me-1"></i>Export CSV
            </a>
        </div>
    </div>

    {{-- Filter --}}
    <div class="kard mb-3 px-3 py-2">
        <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
            <input type="hidden" name="view" value="{{ $viewType }}">
            @if($viewType==='daily')
                <label class="small fw-semibold text-muted mb-0">Dari:</label>
                <input type="date" name="date" class="form-control form-control-sm" style="width:auto;" value="{{ $date }}" max="{{ now()->toDateString() }}">
                <label class="small fw-semibold text-muted mb-0">Sampai:</label>
                <input type="date" name="date_to" class="form-control form-control-sm" style="width:auto;" value="{{ request('date_to', $date) }}" max="{{ now()->toDateString() }}">
            @elseif($viewType==='weekly')
                <label class="small fw-semibold text-muted mb-0">Minggu:</label>
                <input type="week" name="week" class="form-control form-control-sm" style="width:auto;" value="{{ $week }}">
            @else
                <label class="small fw-semibold text-muted mb-0">Bulan:</label>
                <input type="month" name="month" class="form-control form-control-sm" style="width:auto;" value="{{ $month }}">
            @endif
            <button class="btn btn-sm btn-primary" style="border-radius:8px;">Tampilkan</button>
        </form>
    </div>

    {{-- Warning belum upload --}}
    @if($viewType==='daily' && $withoutEmp > 0)
    <div class="mb-3 px-3 py-2 d-flex align-items-center gap-3" style="background:#fffbeb;border-radius:10px;border:1px solid #fde68a;">
        <i class="bi bi-exclamation-triangle-fill" style="color:#d97706;font-size:1rem;flex-shrink:0;"></i>
        <span style="font-size:.83rem;"><b>{{ $withoutEmp }} perusahaan</b> belum upload daftar karyawan hari ini.</span>
        <a href="{{ route('employees.upload') }}" class="btn btn-warning btn-sm ms-auto" style="border-radius:20px;font-size:.75rem;white-space:nowrap;">Upload</a>
    </div>
    @endif

    {{-- STAT CARDS --}}
    @php
        $t = $viewType==='daily'?$dailyTotals:($viewType==='weekly'?$weeklyTotals:$monthlyTotals);
        $cards=[
            ['POB Onsite',        $t['pob'],                              'bi-people-fill',    '--blue',  '#eff6ff'],
            ['Total Manpower',    $t['mp'],                               'bi-person-workspace','--green', '#f0fdf4'],
            ['Rasio POB/MP',      $t['ratio'].'%',                        'bi-graph-up',       '--orange','#fff7ed'],
            ['Perusahaan Lapor',  $t['reporters'].' / '.$totalCompanies,  'bi-building',       '--purple','#faf5ff'],
            ['Karyawan Tercatat', $empToday,                              'bi-person-check',   '--teal',  '#ecfeff'],
        ];
    @endphp
    <div class="row g-3 mb-3">
        @foreach($cards as $c)
        <div class="col-6 col-md-4 col-xl">
            <div class="kard" style="border-top:3px solid var({{ $c[3] }});">
                <div class="card-body px-3 py-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;line-height:1.3;">{{ $c[0] }}</div>
                        <div style="background:{{ $c[4] }};border-radius:7px;padding:5px 7px;">
                            <i class="bi {{ $c[2] }}" style="font-size:1rem;color:var({{ $c[3] }});"></i>
                        </div>
                    </div>
                    <div style="font-size:1.5rem;font-weight:700;color:var({{ $c[3] }});line-height:1;">
                        {{ is_numeric($c[1]) ? number_format($c[1]) : $c[1] }}
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ROW 1: Trend + Donut --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="kard">
                <div class="kard-hdr">
                    <span class="kard-title">📈 Trend POB & Manpower</span>
                    <div style="display:flex;gap:4px;" id="trendBtns">
                        <button class="btn btn-primary btn-sm" style="border-radius:20px;font-size:.73rem;" onclick="swTrend('daily',this)">30 Hari</button>
                        <button class="btn btn-outline-secondary btn-sm" style="border-radius:20px;font-size:.73rem;" onclick="swTrend('weekly',this)">12 Minggu</button>
                        <button class="btn btn-outline-secondary btn-sm" style="border-radius:20px;font-size:.73rem;" onclick="swTrend('monthly',this)">12 Bulan</button>
                    </div>
                </div>
                <div class="card-body p-3" style="position:relative;height:220px;min-height:160px;">
                    <canvas id="cTrend"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="kard h-100">
                <div class="kard-hdr">
                    <span class="kard-title">📊 Distribusi POB</span>
                    <small class="text-muted" style="font-size:.7rem;">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</small>
                </div>
                <div class="card-body p-2" style="position:relative;height:210px;">
                    @php $ctd = $viewType==='daily'?$dailyData:($viewType==='weekly'?$weeklyData:$monthlyData); @endphp
                    @if($ctd->count() > 0)
                    <canvas id="cDonut"></canvas>
                    @else
                    <div class="text-center py-5 text-muted" style="font-size:.82rem;">Belum ada data</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ROW 2: Bar + Dept --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="kard">
                <div class="kard-hdr"><span class="kard-title">🏢 Top 10 POB per Perusahaan</span></div>
                <div class="card-body p-3" style="position:relative;height:210px;">
                    <canvas id="cBar"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="kard">
                <div class="kard-hdr">
                    <span class="kard-title">👥 Karyawan per Departemen</span>
                    <small class="text-muted" style="font-size:.7rem;">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</small>
                </div>
                <div class="card-body p-3" style="position:relative;height:210px;">
                    @if($deptData->count() > 0)
                    <canvas id="cDept"></canvas>
                    @else
                    <div class="text-center py-5 text-muted" style="font-size:.82rem;">
                        <i class="bi bi-person-x" style="font-size:2rem;display:block;opacity:.3;"></i>
                        Belum ada data karyawan<br>
                        <a href="{{ route('employees.upload') }}" class="btn btn-outline-primary btn-sm mt-2" style="font-size:.75rem;border-radius:20px;">Upload</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ROW 3: Emp type + Status upload --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-3">
            <div class="kard h-100">
                <div class="kard-hdr"><span class="kard-title">👤 Karyawan vs Visitor</span></div>
                <div class="card-body p-3" style="position:relative;height:180px;">
                    @if($empEmployee + $empVisitor > 0)
                    <canvas id="cType"></canvas>
                    @else
                    <div class="text-center py-4 text-muted" style="font-size:.82rem;">Belum ada data</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="kard h-100">
                <div class="kard-hdr">
                    <span class="kard-title">📋 Status Laporan Hari Ini</span>
                    <small class="text-muted" style="font-size:.7rem;">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</small>
                </div>
                <div style="max-height:260px;overflow-y:auto;overflow-x:auto;-webkit-overflow-scrolling:touch;">
                    <table class="table table-sm table-hover mb-0 align-middle" style="font-size:.8rem;">
                        <thead style="background:#f8fafc;position:sticky;top:0;">
                            <tr>
                                <th class="px-3 py-2">Perusahaan</th>
                                <th class="text-end">POB</th>
                                <th class="text-end">MP</th>
                                <th class="text-center">Karyawan</th>
                                <th class="text-muted">Pelapor</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($dailyData as $row)
                        <tr>
                            <td class="px-3 fw-semibold">{{ \Illuminate\Support\Str::limit($row->company->name??'-',28) }}</td>
                            <td class="text-end fw-bold" style="color:var(--blue);">{{ $row->total_pob }}</td>
                            <td class="text-end" style="color:var(--green);">{{ $row->total_manpower }}</td>
                            <td class="text-center">
                                @php $ec = $row->employees()->count(); @endphp
                                @if($ec > 0)
                                <a href="{{ route('employees.index') }}?date={{ $date }}&company_id={{ $row->company_id }}"
                                   style="background:#dcfce7;color:#16a34a;border-radius:20px;font-size:.72rem;padding:2px 10px;text-decoration:none;font-weight:600;">
                                    {{ $ec }} ✔
                                </a>
                                @else
                                <span style="background:#fef9c3;color:#ca8a04;border-radius:20px;font-size:.72rem;padding:2px 10px;font-weight:600;">Belum</span>
                                @endif
                            </td>
                            <td style="color:#94a3b8;font-size:.77rem;">{{ $row->informed_by??'-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada laporan hari ini</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- TABEL DETAIL --}}
    @php
        $tbl = $viewType==='daily'?$dailyData:($viewType==='weekly'?$weeklyData:$monthlyData);
    @endphp
    <div class="kard">
        <div class="kard-hdr">
            <span class="kard-title">📄 Detail per Perusahaan</span>
            <span style="background:#2563eb;color:#fff;border-radius:20px;font-size:.72rem;padding:2px 10px;">{{ $tbl->count() }} perusahaan</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:.82rem;">
                <thead style="background:#f8fafc;font-size:.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;">
                    <tr>
                        <th class="px-3 py-2">#</th><th>Perusahaan</th>
                        <th class="text-end">POB</th><th class="text-end">Manpower</th>
                        <th class="text-end">Rasio</th><th style="min-width:120px;">Distribusi</th>
                        @if($viewType==='daily')
                        <th class="text-center">Karyawan</th><th>Pelapor</th>
                        @else
                        <th class="text-center">Hari Lapor</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                @forelse($tbl as $i => $r)
                @php
                    $pob=$viewType==='daily'?$r->total_pob:($r->pob??0);
                    $mp=$viewType==='daily'?$r->total_manpower:($r->mp??0);
                    $rat=$mp>0?round($pob/$mp*100,1):0;
                    $pct=$t['pob']>0?round($pob/$t['pob']*100,1):0;
                    $rc=$rat>=80?'#16a34a':($rat>=50?'#ea580c':'#94a3b8');
                @endphp
                <tr>
                    <td class="px-3" style="color:#cbd5e1;font-size:.72rem;">{{ $i+1 }}</td>
                    <td class="fw-semibold">{{ $r->company->name??'-' }}</td>
                    <td class="text-end fw-bold" style="color:var(--blue);">{{ number_format($pob) }}</td>
                    <td class="text-end" style="color:var(--green);">{{ number_format($mp) }}</td>
                    <td class="text-end"><span style="background:{{ $rc }};color:#fff;border-radius:20px;padding:2px 9px;font-size:.72rem;font-weight:600;">{{ $rat }}%</span></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="flex:1;height:5px;background:#e2e8f0;border-radius:3px;overflow:hidden;">
                                <div style="height:5px;width:{{ $pct }}%;background:var(--blue);border-radius:3px;"></div>
                            </div>
                            <span style="font-size:.68rem;color:#cbd5e1;">{{ $pct }}%</span>
                        </div>
                    </td>
                    @if($viewType==='daily')
                    <td class="text-center">
                        @php $ec=$r->employees()->count(); @endphp
                        @if($ec>0)<a href="{{ route('employees.index') }}?date={{ $date }}&company_id={{ $r->company_id }}" style="color:var(--green);font-size:.75rem;font-weight:600;text-decoration:none;">{{ $ec }} ✔</a>
                        @else<span style="color:#fbbf24;font-size:.75rem;">–</span>@endif
                    </td>
                    <td style="color:#94a3b8;font-size:.77rem;">{{ \Illuminate\Support\Str::limit($r->informed_by??'-',20) }}</td>
                    @else
                    <td class="text-center" style="color:#94a3b8;font-size:.77rem;">{{ $r->days??0 }} hari</td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-5" style="color:#cbd5e1;">
                    <i class="bi bi-inbox" style="font-size:2rem;display:block;opacity:.3;margin-bottom:6px;"></i>Belum ada data
                </td></tr>
                @endforelse
                </tbody>
                @if($tbl->count()>0)
                <tfoot style="background:#f8fafc;font-size:.82rem;font-weight:700;">
                    <tr>
                        <td colspan="2" class="px-3 py-2">TOTAL</td>
                        <td class="text-end" style="color:var(--blue);">{{ number_format($t['pob']) }}</td>
                        <td class="text-end" style="color:var(--green);">{{ number_format($t['mp']) }}</td>
                        <td class="text-end"><span style="background:var(--blue);color:#fff;border-radius:20px;padding:2px 10px;font-size:.72rem;">{{ $t['ratio']??0 }}%</span></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Data dari PHP (encode sekali, tidak looping) ──────────────────
const TREND = {
    daily: {
        labels: {!! json_encode(array_map(fn($r) => \Carbon\Carbon::parse($r->d)->format('d M'), $trendDaily)) !!},
        pob:    {!! json_encode(array_map(fn($r) => (int)$r->pob, $trendDaily)) !!},
        mp:     {!! json_encode(array_map(fn($r) => (int)$r->mp,  $trendDaily)) !!},
    },
    weekly: {
        labels: {!! json_encode(array_map(fn($r) => \Carbon\Carbon::parse($r->d)->format('d M'), $trendWeekly)) !!},
        pob:    {!! json_encode(array_map(fn($r) => (int)$r->pob, $trendWeekly)) !!},
        mp:     {!! json_encode(array_map(fn($r) => (int)$r->mp,  $trendWeekly)) !!},
    },
    monthly: {
        labels: {!! json_encode(array_map(fn($r) => \Carbon\Carbon::parse($r->d)->format('M Y'), $trendMonthly)) !!},
        pob:    {!! json_encode(array_map(fn($r) => (int)$r->pob, $trendMonthly)) !!},
        mp:     {!! json_encode(array_map(fn($r) => (int)$r->mp,  $trendMonthly)) !!},
    },
};

@php
    $ctd  = $viewType==='daily'?$dailyData:($viewType==='weekly'?$weeklyData:$monthlyData);
    $top  = $ctd->sortByDesc(fn($r)=>$viewType==='daily'?$r->total_pob:($r->pob??0))->take(10)->values();
    $bLbl = $top->map(fn($r)=>$r->company->name??'N/A')->toArray();
    $bPob = $top->map(fn($r)=>(int)($viewType==='daily'?$r->total_pob:($r->pob??0)))->toArray();
    $dLbl = $ctd->map(fn($r)=>$r->company->name??'N/A')->values()->toArray();
    $dPob = $ctd->map(fn($r)=>(int)($viewType==='daily'?$r->total_pob:($r->pob??0)))->values()->toArray();
@endphp

const BAR_LABELS = {!! json_encode($bLbl) !!};
const BAR_POB    = {!! json_encode($bPob) !!};
const DNT_LABELS = {!! json_encode($dLbl) !!};
const DNT_POB    = {!! json_encode($dPob) !!};
const DEPT_L     = {!! json_encode($deptData->pluck('department')->toArray()) !!};
const DEPT_V     = {!! json_encode($deptData->pluck('total')->map(fn($v)=>(int)$v)->toArray()) !!};
const EMP_E      = {{ $empEmployee }};
const EMP_V      = {{ $empVisitor }};

const PAL=['#2563eb','#16a34a','#ea580c','#7c3aed','#0891b2','#d97706','#dc2626','#0d9488','#9333ea','#65a30d'];

Chart.defaults.font.family = "'Segoe UI',sans-serif";
Chart.defaults.color = '#94a3b8';

// ── Trend Chart (satu instance, tidak diinit ulang saat toggle) ──
const tCtx = document.getElementById('cTrend').getContext('2d');
const tChart = new Chart(tCtx, {
    type: 'line',
    data: {
        labels: TREND.daily.labels,
        datasets: [
            {label:'POB',data:TREND.daily.pob,borderColor:'#2563eb',backgroundColor:'rgba(37,99,235,.06)',tension:.4,fill:true,pointRadius:2,pointHoverRadius:5,borderWidth:2},
            {label:'Manpower',data:TREND.daily.mp,borderColor:'#16a34a',backgroundColor:'rgba(22,163,74,.06)',tension:.4,fill:true,pointRadius:2,pointHoverRadius:5,borderWidth:2},
        ]
    },
    options:{
        responsive:true, maintainAspectRatio:false,
        interaction:{mode:'index',intersect:false},
        plugins:{legend:{position:'top',labels:{usePointStyle:true,pointStyleWidth:8,boxHeight:6,font:{size:11}}}},
        scales:{
            y:{grid:{color:'rgba(0,0,0,.04)'},ticks:{maxTicksLimit:5}},
            x:{grid:{display:false},ticks:{maxTicksLimit:8,maxRotation:0}}
        }
    }
});

// Toggle trend — update data tanpa destroy/recreate
function swTrend(key, btn) {
    document.querySelectorAll('#trendBtns button').forEach(b => {
        b.className = 'btn btn-outline-secondary btn-sm';
        b.style.borderRadius = '20px'; b.style.fontSize = '.73rem';
    });
    btn.className = 'btn btn-primary btn-sm';
    btn.style.borderRadius = '20px'; btn.style.fontSize = '.73rem';

    tChart.data.labels           = TREND[key].labels;
    tChart.data.datasets[0].data = TREND[key].pob;
    tChart.data.datasets[1].data = TREND[key].mp;
    tChart.update('active');
}

// ── Donut ──
@if($ctd->count() > 0)
new Chart(document.getElementById('cDonut').getContext('2d'), {
    type:'doughnut',
    data:{
        labels:DNT_LABELS,
        datasets:[{data:DNT_POB,backgroundColor:PAL,borderWidth:2,borderColor:'#fff',hoverOffset:4}]
    },
    options:{
        responsive:true, maintainAspectRatio:false, cutout:'60%',
        plugins:{
            legend:{position:'bottom',labels:{usePointStyle:true,font:{size:9},padding:6}},
            tooltip:{callbacks:{label:ctx=>{
                const tot=DNT_POB.reduce((a,b)=>a+b,0);
                return ` ${ctx.label}: ${ctx.parsed.toLocaleString()} (${tot>0?((ctx.parsed/tot)*100).toFixed(1):0}%)`;
            }}}
        }
    }
});
@endif

// ── Bar horizontal ──
new Chart(document.getElementById('cBar').getContext('2d'), {
    type:'bar',
    data:{
        labels:BAR_LABELS.map(l=>l.length>22?l.slice(0,20)+'…':l),
        datasets:[{label:'POB',data:BAR_POB,backgroundColor:PAL,borderRadius:4}]
    },
    options:{
        indexAxis:'y', responsive:true, maintainAspectRatio:false,
        plugins:{legend:{display:false}},
        scales:{
            x:{grid:{color:'rgba(0,0,0,.04)'},beginAtZero:true,ticks:{maxTicksLimit:5}},
            y:{grid:{display:false},ticks:{font:{size:10}}}
        }
    }
});

// ── Dept bar ──
@if($deptData->count() > 0)
new Chart(document.getElementById('cDept').getContext('2d'), {
    type:'bar',
    data:{
        labels:DEPT_L,
        datasets:[{label:'Karyawan',data:DEPT_V,backgroundColor:'rgba(37,99,235,.75)',borderRadius:4}]
    },
    options:{
        responsive:true, maintainAspectRatio:false,
        plugins:{legend:{display:false}},
        scales:{
            y:{grid:{color:'rgba(0,0,0,.04)'},beginAtZero:true,ticks:{precision:0,maxTicksLimit:5}},
            x:{grid:{display:false},ticks:{font:{size:10},maxRotation:30}}
        }
    }
});
@endif

// ── Emp type ──
@if($empEmployee + $empVisitor > 0)
new Chart(document.getElementById('cType').getContext('2d'), {
    type:'doughnut',
    data:{
        labels:['Karyawan','Visitor'],
        datasets:[{data:[EMP_E,EMP_V],backgroundColor:['#2563eb','#ea580c'],borderWidth:2,borderColor:'#fff'}]
    },
    options:{
        responsive:true, maintainAspectRatio:false, cutout:'55%',
        plugins:{legend:{position:'bottom',labels:{usePointStyle:true,font:{size:10},padding:6}}}
    }
});
@endif
</script>
@endpush
