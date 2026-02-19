@extends('layouts.app')
@section('title','Dashboard POB & Manpower')
@section('content')
<style>
:root{--blue:#2563eb;--green:#16a34a;--orange:#ea580c;--purple:#7c3aed;--teal:#0891b2;}
.sb-link{display:flex;align-items:center;gap:10px;padding:9px 14px;border-radius:8px;color:rgba(255,255,255,.7);text-decoration:none;font-size:.85rem;transition:all .15s;margin-bottom:2px;}
.sb-link:hover,.sb-link.on{background:rgba(255,255,255,.15);color:#fff;}
.kard{background:#fff;border-radius:14px;border:none;box-shadow:0 1px 8px rgba(0,0,0,.07);}
.kard-header{padding:14px 18px 10px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;}
.kard-title{font-size:.82rem;font-weight:700;color:#374151;}
.pill{border-radius:20px;font-size:.75rem;padding:3px 12px;cursor:pointer;border:1px solid #e2e8f0;background:#f8fafc;color:#64748b;transition:all .15s;}
.pill.on{background:var(--blue);color:#fff;border-color:var(--blue);}
</style>

<div class="d-flex" style="min-height:100vh;">

{{-- SIDEBAR --}}
<nav style="width:216px;min-height:100vh;background:#1a3c5e;flex-shrink:0;display:flex;flex-direction:column;padding:14px 10px;">
    <div style="text-align:center;padding:8px 0 18px;">
        <div style="font-size:1.05rem;font-weight:700;color:#fff;">&#x26CF; SCM Nickel</div>
        <div style="font-size:.68rem;color:rgba(255,255,255,.35);">POB & Manpower System</div>
    </div>
    <a href="{{ route('dashboard') }}" class="sb-link {{ request()->routeIs('dashboard') ? 'on' : '' }}"><i class="bi bi-speedometer2"></i>Dashboard</a>
    <a href="{{ route('employees.index') }}" class="sb-link {{ request()->routeIs('employees.index') ? 'on' : '' }}"><i class="bi bi-people"></i>Data Karyawan</a>
    <a href="{{ route('employees.upload') }}" class="sb-link {{ request()->routeIs('employees.upload') ? 'on' : '' }}"><i class="bi bi-person-plus"></i>Upload Karyawan</a>
    <a href="{{ route('dashboard.import') }}" class="sb-link {{ request()->routeIs('dashboard.import') ? 'on' : '' }}"><i class="bi bi-upload"></i>Import POB</a>
    <div style="margin-top:auto;padding-top:14px;border-top:1px solid rgba(255,255,255,.1);">
        <div style="font-size:.7rem;color:rgba(255,255,255,.35);padding:0 14px 6px;">{{ auth()->user()->name }}</div>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button style="width:100%;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.75);border-radius:8px;padding:7px;font-size:.8rem;cursor:pointer;">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </button>
        </form>
    </div>
</nav>

{{-- MAIN --}}
<main style="flex:1;background:#f0f4f8;padding:22px;overflow-x:hidden;">

{{-- Top --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0" style="color:#1e293b;">Dashboard POB & Manpower</h5>
        <small class="text-muted">Update terakhir: <b>{{ $latestDate ? \Carbon\Carbon::parse($latestDate)->format('d M Y') : '-' }}</b> &middot; {{ number_format($totalEntries) }} total entri</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <div class="d-flex" style="background:#fff;border-radius:22px;padding:3px;box-shadow:0 1px 4px rgba(0,0,0,.08);">
            <a href="{{ route('dashboard') }}?view=daily&date={{ $date }}" class="pill {{ $viewType==='daily'?'on':'' }}">Harian</a>
            <a href="{{ route('dashboard') }}?view=weekly&week={{ $week }}" class="pill {{ $viewType==='weekly'?'on':'' }}">Mingguan</a>
            <a href="{{ route('dashboard') }}?view=monthly&month={{ $month }}" class="pill {{ $viewType==='monthly'?'on':'' }}">Bulanan</a>
        </div>
        <a href="{{ route('dashboard.export') }}?from={{ $date }}&to={{ $date }}" class="btn btn-sm btn-outline-success" style="border-radius:20px;font-size:.8rem;">
            <i class="bi bi-download me-1"></i>Export
        </a>
    </div>
</div>

{{-- Filter --}}
<div class="kard mb-3 px-3 py-2">
    <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
        <input type="hidden" name="view" value="{{ $viewType }}">
        @if($viewType==='daily')
            <input type="date" name="date" class="form-control form-control-sm" style="width:auto;" value="{{ $date }}" max="{{ now()->toDateString() }}">
        @elseif($viewType==='weekly')
            <input type="week" name="week" class="form-control form-control-sm" style="width:auto;" value="{{ $week }}">
        @else
            <input type="month" name="month" class="form-control form-control-sm" style="width:auto;" value="{{ $month }}">
        @endif
        <button class="btn btn-sm btn-primary" style="border-radius:8px;">Tampilkan</button>
    </form>
</div>

{{-- Warning belum upload karyawan --}}
@if($viewType==='daily' && $withoutEmp > 0)
<div class="mb-3 d-flex align-items-center gap-3 px-3 py-2"
     style="background:#fffbeb;border-radius:10px;border:1px solid #fde68a;">
    <i class="bi bi-exclamation-triangle-fill" style="color:#d97706;font-size:1.1rem;flex-shrink:0;"></i>
    <span style="font-size:.83rem;"><strong>{{ $withoutEmp }} perusahaan</strong> belum mengupload daftar karyawan hari ini.</span>
    <a href="{{ route('employees.upload') }}" class="btn btn-warning btn-sm ms-auto" style="border-radius:20px;font-size:.75rem;white-space:nowrap;">Upload Sekarang</a>
</div>
@endif

{{-- ===== STAT CARDS ===== --}}
@php
    $t = $viewType==='daily'?$dailyTotals:($viewType==='weekly'?$weeklyTotals:$monthlyTotals);
    $sc = [
        ['POB Onsite',         $t['pob'],           'bi-people-fill',    '--blue',   '#eff6ff'],
        ['Total Manpower',     $t['mp'],            'bi-person-workspace','--green',  '#f0fdf4'],
        ['Rasio POB/MP',       $t['ratio'].'%',     'bi-graph-up',       '--orange', '#fff7ed'],
        ['Perusahaan Lapor',   $t['reporters'].' / '.$totalCompanies, 'bi-building','--purple','#faf5ff'],
        ['Karyawan Tercatat',  $empToday,           'bi-person-check',   '--teal',   '#ecfeff'],
    ];
@endphp
<div class="row g-3 mb-3">
    @foreach($sc as $c)
    <div class="col-6 col-xl">
        <div class="kard" style="border-top:3px solid var({{ $c[3] }});">
            <div class="card-body px-3 py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">{{ $c[0] }}</div>
                        <div style="font-size:1.55rem;font-weight:700;color:var({{ $c[3] }});line-height:1.1;">{{ is_numeric($c[1]) ? number_format($c[1]) : $c[1] }}</div>
                    </div>
                    <div style="background:{{ $c[4] }};border-radius:8px;padding:6px 8px;">
                        <i class="bi {{ $c[2] }}" style="font-size:1.1rem;color:var({{ $c[3] }});"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ===== ROW 1: Trend + Donut ===== --}}
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="kard">
            <div class="kard-header">
                <span class="kard-title">&#x1F4C8; Trend POB & Manpower</span>
                <div class="d-flex gap-1" id="trendPills">
                    <button class="pill on" onclick="swTrend('daily',this)">30 Hari</button>
                    <button class="pill" onclick="swTrend('weekly',this)">12 Minggu</button>
                    <button class="pill" onclick="swTrend('monthly',this)">12 Bulan</button>
                </div>
            </div>
            <div class="card-body p-3">
                <canvas id="cTrend" style="max-height:210px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="kard h-100">
            <div class="kard-header">
                <span class="kard-title">&#x1F4CA; Distribusi POB</span>
                <small class="text-muted" style="font-size:.7rem;">
                    {{ $viewType==='daily' ? \Carbon\Carbon::parse($date)->format('d M Y') : ($viewType==='weekly' ? 'Minggu '.$week : $month) }}
                </small>
            </div>
            <div class="card-body p-2 d-flex align-items-center">
                @php
                    $ctd = $viewType==='daily'?$dailyData:($viewType==='weekly'?$weeklyData:$monthlyData);
                    $hasDist = $ctd->count() > 0;
                @endphp
                @if($hasDist)
                <canvas id="cDonut" style="max-height:220px;"></canvas>
                @else
                <div class="text-center w-100 py-5 text-muted" style="font-size:.82rem;">Belum ada data</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ===== ROW 2: Bar + Dept ===== --}}
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="kard">
            <div class="kard-header">
                <span class="kard-title">&#x1F3C6; Top 10 POB per Perusahaan</span>
            </div>
            <div class="card-body p-3">
                <canvas id="cBar" style="max-height:200px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="kard">
            <div class="kard-header">
                <span class="kard-title">&#x1F465; Karyawan per Departemen</span>
                <small class="text-muted" style="font-size:.7rem;">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</small>
            </div>
            <div class="card-body p-3">
                @if($deptBreakdown->count() > 0)
                <canvas id="cDept" style="max-height:200px;"></canvas>
                @else
                <div class="text-center py-4 text-muted" style="font-size:.82rem;">
                    <i class="bi bi-person-x" style="font-size:1.8rem;display:block;opacity:.3;"></i>
                    Belum ada data karyawan hari ini<br>
                    <a href="{{ route('employees.upload') }}" class="btn btn-outline-primary btn-sm mt-2" style="font-size:.75rem;border-radius:20px;">Upload Karyawan</a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ===== ROW 3: Employee type + Pelapor ===== --}}
<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="kard">
            <div class="kard-header"><span class="kard-title">&#x1F464; Komposisi Karyawan vs Visitor</span></div>
            <div class="card-body p-3">
                @if($empTypeBreakdown->sum() > 0)
                <canvas id="cEmpType" style="max-height:160px;"></canvas>
                @else
                <div class="text-center py-4 text-muted" style="font-size:.82rem;">Belum ada data</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="kard">
            <div class="kard-header">
                <span class="kard-title">&#x1F4CB; Status Upload Karyawan Hari Ini</span>
                <small class="text-muted" style="font-size:.7rem;">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</small>
            </div>
            <div class="card-body p-0">
                <div style="max-height:190px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0" style="font-size:.8rem;">
                        <thead style="background:#f8fafc;position:sticky;top:0;">
                            <tr>
                                <th class="px-3 py-2">Perusahaan</th>
                                <th class="py-2 text-end">POB</th>
                                <th class="py-2 text-end">MP</th>
                                <th class="py-2 text-center">Data Karyawan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dailyData as $row)
                            <tr>
                                <td class="px-3 fw-semibold" style="max-width:180px;">{{ Str::limit($row->company->name??'-',28) }}</td>
                                <td class="text-end text-primary fw-bold">{{ number_format($row->total_pob) }}</td>
                                <td class="text-end text-success">{{ number_format($row->total_manpower) }}</td>
                                <td class="text-center">
                                    @php $ec = $row->employees()->count(); @endphp
                                    @if($ec > 0)
                                    <a href="{{ route('employees.index') }}?date={{ $date }}&company_id={{ $row->company_id }}"
                                       style="background:#dcfce7;color:#16a34a;border-radius:20px;font-size:.72rem;padding:2px 10px;text-decoration:none;font-weight:600;">
                                        {{ $ec }} orang &#x2714;
                                    </a>
                                    @else
                                    <span style="background:#fef9c3;color:#ca8a04;border-radius:20px;font-size:.72rem;padding:2px 10px;font-weight:600;">Belum ada</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada laporan hari ini</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== TABEL DETAIL ===== --}}
@php
    $tbl = $viewType==='daily'?$dailyData:($viewType==='weekly'?$weeklyData:$monthlyData);
    $tt  = $t;
@endphp
<div class="kard">
    <div class="kard-header">
        <span class="kard-title">&#x1F4C4; Detail per Perusahaan — {{ $viewType==='daily' ? \Carbon\Carbon::parse($date)->format('d M Y') : ($viewType==='weekly'?'Minggu '.$week:$month) }}</span>
        <span style="background:#2563eb;color:#fff;border-radius:20px;font-size:.72rem;padding:2px 10px;">{{ $tbl->count() }} perusahaan</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:.83rem;">
                <thead style="background:#f8fafc;font-size:.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">
                    <tr>
                        <th class="px-3 py-2">#</th>
                        <th>Perusahaan</th>
                        <th class="text-end">POB</th>
                        <th class="text-end">Manpower</th>
                        <th class="text-end">Rasio</th>
                        <th style="min-width:130px;">Distribusi</th>
                        @if($viewType==='daily')
                        <th class="text-center">Karyawan</th>
                        <th>Pelapor</th>
                        @else
                        <th class="text-center">Hari Lapor</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                @forelse($tbl as $i => $r)
                @php
                    $pob  = $viewType==='daily' ? $r->total_pob : ($r->pob??0);
                    $mp   = $viewType==='daily' ? $r->total_manpower : ($r->mp??0);
                    $rat  = $mp>0 ? round($pob/$mp*100,1) : 0;
                    $pct  = $tt['pob']>0 ? round($pob/$tt['pob']*100,1) : 0;
                    $rc   = $rat>=80?'#16a34a':($rat>=50?'#ea580c':'#94a3b8');
                @endphp
                <tr>
                    <td class="px-3" style="color:#cbd5e1;font-size:.75rem;">{{ $i+1 }}</td>
                    <td class="fw-semibold">{{ $r->company->name??'-' }}</td>
                    <td class="text-end fw-bold" style="color:var(--blue);">{{ number_format($pob) }}</td>
                    <td class="text-end" style="color:var(--green);">{{ number_format($mp) }}</td>
                    <td class="text-end">
                        <span style="background:{{ $rc }};color:#fff;border-radius:20px;padding:2px 9px;font-size:.72rem;font-weight:600;">{{ $rat }}%</span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="flex:1;height:5px;background:#e2e8f0;border-radius:3px;overflow:hidden;">
                                <div style="height:5px;width:{{ $pct }}%;background:var(--blue);border-radius:3px;"></div>
                            </div>
                            <span style="font-size:.7rem;color:#cbd5e1;white-space:nowrap;">{{ $pct }}%</span>
                        </div>
                    </td>
                    @if($viewType==='daily')
                    <td class="text-center">
                        @php $ec = $r->employees()->count(); @endphp
                        @if($ec>0)
                        <a href="{{ route('employees.index') }}?date={{ $date }}&company_id={{ $r->company_id }}"
                           style="color:var(--green);font-size:.75rem;font-weight:600;text-decoration:none;">{{ $ec }} &#x2714;</a>
                        @else
                        <span style="color:#fbbf24;font-size:.75rem;">–</span>
                        @endif
                    </td>
                    <td style="color:#94a3b8;font-size:.78rem;">{{ Str::limit($r->informed_by??'-',20) }}</td>
                    @else
                    <td class="text-center" style="color:#94a3b8;font-size:.78rem;">{{ $r->days??0 }} hari</td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-5" style="color:#cbd5e1;">
                    <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:6px;opacity:.4;"></i>Belum ada data
                </td></tr>
                @endforelse
                </tbody>
                @if($tbl->count()>0)
                <tfoot style="background:#f8fafc;font-size:.82rem;font-weight:700;">
                    <tr>
                        <td colspan="2" class="px-3 py-2">TOTAL</td>
                        <td class="text-end" style="color:var(--blue);">{{ number_format($tt['pob']) }}</td>
                        <td class="text-end" style="color:var(--green);">{{ number_format($tt['mp']) }}</td>
                        <td class="text-end"><span style="background:var(--blue);color:#fff;border-radius:20px;padding:2px 10px;font-size:.72rem;">{{ $tt['ratio']??0 }}%</span></td>
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
Chart.defaults.font.family="'Segoe UI',sans-serif";
Chart.defaults.color='#94a3b8';
Chart.defaults.plugins.legend.labels.usePointStyle=true;
Chart.defaults.plugins.legend.labels.pointStyleWidth=8;
Chart.defaults.plugins.legend.labels.boxHeight=6;

@php
    $td = $trendData->map(fn($r)=>['d'=>\Carbon\Carbon::parse($r->date)->format('d M'),'p'=>(int)$r->pob,'m'=>(int)$r->mp]);
    $tw = $weeklyTrend->map(fn($r)=>['d'=>\Carbon\Carbon::parse($r->week_start)->format('d M'),'p'=>(int)$r->pob,'m'=>(int)$r->mp]);
    $tm = $monthlyTrend->map(fn($r)=>['d'=>\Carbon\Carbon::parse($r->period.'-01')->format('M Y'),'p'=>(int)$r->pob,'m'=>(int)$r->mp]);

    $ctd  = $viewType==='daily'?$dailyData:($viewType==='weekly'?$weeklyData:$monthlyData);
    $top  = $ctd->sortByDesc(fn($r)=>$viewType==='daily'?$r->total_pob:($r->pob??0))->take(10);
    $bLbl = $top->map(fn($r)=>$r->company->name??'N/A')->values()->toArray();
    $bPob = $top->map(fn($r)=>(int)($viewType==='daily'?$r->total_pob:($r->pob??0)))->values()->toArray();
    $dLbl = $ctd->map(fn($r)=>$r->company->name??'N/A')->values()->toArray();
    $dPob = $ctd->map(fn($r)=>(int)($viewType==='daily'?$r->total_pob:($r->pob??0)))->values()->toArray();
    $deptL= $deptBreakdown->pluck('department')->toArray();
    $deptV= $deptBreakdown->pluck('total')->map(fn($v)=>(int)$v)->toArray();
    $empEmp = (int)($empTypeBreakdown['employee'] ?? 0);
    $empVis = (int)($empTypeBreakdown['visitor']  ?? 0);
@endphp

const TDATA={
    daily:  {!! json_encode($td->values()) !!},
    weekly: {!! json_encode($tw->values()) !!},
    monthly:{!! json_encode($tm->values()) !!},
};
const PAL=['#2563eb','#16a34a','#ea580c','#7c3aed','#0891b2','#d97706','#dc2626','#0d9488','#9333ea','#65a30d'];

// ── Trend ──
const tCtx=document.getElementById('cTrend');
let tChart;
function buildTrend(key){
    const d=TDATA[key];
    const cfg={
        type:'line',
        data:{
            labels:d.map(x=>x.d),
            datasets:[
                {label:'POB',data:d.map(x=>x.p),borderColor:'#2563eb',backgroundColor:'rgba(37,99,235,.06)',tension:.4,fill:true,pointRadius:2,pointHoverRadius:5,borderWidth:2},
                {label:'Manpower',data:d.map(x=>x.m),borderColor:'#16a34a',backgroundColor:'rgba(22,163,74,.06)',tension:.4,fill:true,pointRadius:2,pointHoverRadius:5,borderWidth:2},
            ]
        },
        options:{
            responsive:true,maintainAspectRatio:true,
            interaction:{mode:'index',intersect:false},
            plugins:{legend:{position:'top'}},
            scales:{
                y:{grid:{color:'rgba(0,0,0,.04)'},ticks:{maxTicksLimit:6}},
                x:{grid:{display:false},ticks:{maxTicksLimit:10,maxRotation:0}}
            }
        }
    };
    if(tChart)tChart.destroy();
    tChart=new Chart(tCtx,cfg);
}
buildTrend('daily');
function swTrend(k,btn){
    document.querySelectorAll('#trendPills .pill').forEach(b=>{b.classList.remove('on');});
    btn.classList.add('on');
    buildTrend(k);
}

// ── Donut ──
@if($hasDist)
new Chart(document.getElementById('cDonut'),{
    type:'doughnut',
    data:{
        labels:{!! json_encode($dLbl) !!},
        datasets:[{data:{!! json_encode($dPob) !!},backgroundColor:PAL,borderWidth:2,borderColor:'#fff',hoverOffset:6}]
    },
    options:{
        responsive:true,maintainAspectRatio:true,cutout:'60%',
        plugins:{
            legend:{position:'bottom',labels:{font:{size:9},padding:6}},
            tooltip:{callbacks:{label:ctx=>{
                const t={!! json_encode($dPob) !!}.reduce((a,b)=>a+b,0);
                return ` ${ctx.label}: ${ctx.parsed.toLocaleString()} (${t>0?((ctx.parsed/t)*100).toFixed(1):0}%)`;
            }}}
        }
    }
});
@endif

// ── Bar horizontal ──
new Chart(document.getElementById('cBar'),{
    type:'bar',
    data:{
        labels:{!! json_encode($bLbl) !!}.map(l=>l.length>24?l.slice(0,22)+'…':l),
        datasets:[{label:'POB',data:{!! json_encode($bPob) !!},backgroundColor:PAL,borderRadius:4}]
    },
    options:{
        indexAxis:'y',responsive:true,maintainAspectRatio:true,
        plugins:{legend:{display:false}},
        scales:{
            x:{grid:{color:'rgba(0,0,0,.04)'},beginAtZero:true,ticks:{maxTicksLimit:5}},
            y:{grid:{display:false},ticks:{font:{size:10}}}
        }
    }
});

// ── Dept chart ──
@if($deptBreakdown->count() > 0)
new Chart(document.getElementById('cDept'),{
    type:'bar',
    data:{
        labels:{!! json_encode($deptL) !!},
        datasets:[{label:'Karyawan',data:{!! json_encode($deptV) !!},backgroundColor:'rgba(37,99,235,.7)',borderRadius:4}]
    },
    options:{
        responsive:true,maintainAspectRatio:true,
        plugins:{legend:{display:false}},
        scales:{
            y:{grid:{color:'rgba(0,0,0,.04)'},beginAtZero:true,ticks:{precision:0,maxTicksLimit:5}},
            x:{grid:{display:false},ticks:{font:{size:10},maxRotation:30}}
        }
    }
});
@endif

// ── Emp type doughnut ──
@if($empTypeBreakdown->sum() > 0)
new Chart(document.getElementById('cEmpType'),{
    type:'doughnut',
    data:{
        labels:['Karyawan','Visitor'],
        datasets:[{data:[{{ $empEmp }},{{ $empVis }}],backgroundColor:['#2563eb','#ea580c'],borderWidth:2,borderColor:'#fff'}]
    },
    options:{
        responsive:true,maintainAspectRatio:true,cutout:'55%',
        plugins:{legend:{position:'bottom',labels:{font:{size:10},padding:8}}}
    }
});
@endif
</script>
@endpush
