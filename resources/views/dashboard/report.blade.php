@extends('layouts.app')
@section('title','Laporan POB')
@push('styles')
<style>
.diff-up{color:#16a34a;font-weight:600;}
.diff-dn{color:#dc2626;font-weight:600;}
.diff-eq{color:#94a3b8;}
.badge-ok{background:#dcfce7;color:#16a34a;border-radius:20px;font-size:.72rem;padding:2px 9px;font-weight:600;}
.badge-no{background:#fee2e2;color:#dc2626;border-radius:20px;font-size:.72rem;padding:2px 9px;font-weight:600;}
.pill-row{display:flex;background:#f1f5f9;border-radius:20px;padding:2px;gap:0;}
.pill-row a{border-radius:18px;padding:4px 14px;font-size:.78rem;color:#64748b;text-decoration:none;transition:all .15s;white-space:nowrap;}
.pill-row a.on{background:#2563eb;color:#fff;}
.kard{background:#fff;border-radius:12px;box-shadow:0 1px 8px rgba(0,0,0,.07);}
.kard-hdr{padding:13px 16px 10px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;}
.kard-title{font-size:.82rem;font-weight:700;color:#374151;}
</style>
@endpush
@section('content')
<div class="d-flex">
@include('partials.sidebar')
<main style="flex:1;padding:22px;background:#f0f4f8;overflow-x:hidden;">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0">Laporan POB & Manpower</h5>
            <small class="text-muted">Analisa kehadiran dan performa per periode</small>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <div class="pill-row">
                <a href="{{ route('report.index') }}?view=weekly" class="{{ $view==='weekly'?'on':'' }}">Mingguan</a>
                <a href="{{ route('report.index') }}?view=monthly" class="{{ $view==='monthly'?'on':'' }}">Bulanan</a>
                <a href="{{ route('report.index') }}?view=yearly" class="{{ $view==='yearly'?'on':'' }}">Tahunan</a>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="kard mb-3 px-3 py-2">
        <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
            <input type="hidden" name="view" value="{{ $view }}">
            @if($view==='weekly')
                <label class="small fw-semibold text-muted mb-0">Pilih Minggu:</label>
                <input type="week" name="week" class="form-control form-control-sm" style="width:auto;" value="{{ $weekInput }}">
            @elseif($view==='monthly')
                <label class="small fw-semibold text-muted mb-0">Pilih Bulan:</label>
                <input type="month" name="month" class="form-control form-control-sm" style="width:auto;" value="{{ $month }}">
            @else
                <label class="small fw-semibold text-muted mb-0">Pilih Tahun:</label>
                <select name="year" class="form-select form-select-sm" style="width:auto;">
                    @for($y = now()->year; $y >= now()->year - 4; $y--)
                    <option value="{{ $y }}" {{ (isset($year) && $year==$y) ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            @endif
            <button class="btn btn-sm btn-primary" style="border-radius:8px;">Tampilkan</button>
        </form>
    </div>

    {{-- ── WEEKLY VIEW ── --}}
    @if($view === 'weekly')
    @php
        $pobDiff = (int)$totalPob - (int)$totalPrevPob;
        $mpDiff  = (int)$totalMp  - (int)$totalPrevMp;
    @endphp

    {{-- Stat Cards --}}
    <div class="row g-3 mb-3">
        @php $scards = [
            ['Total POB Minggu Ini',     number_format((int)$totalPob),  (int)$pobDiff, 'bi-people-fill',    '#2563eb','#eff6ff'],
            ['Total Manpower',           number_format((int)$totalMp),   (int)$mpDiff,  'bi-person-workspace','#16a34a','#f0fdf4'],
            ['Perusahaan Lapor Cukup',   $metMinimum.' perusahaan', null,     'bi-check-circle',   '#16a34a','#f0fdf4'],
            ['Belum Cukup / Tidak Lapor',($notMetMinimum + $notReported->count()).' perusahaan', null,'bi-exclamation-circle','#dc2626','#fef2f2'],
        ]; @endphp
        @foreach($scards as $c)
        <div class="col-6 col-xl-3">
            <div class="kard" style="border-top:3px solid {{ $c[4] }};">
                <div class="card-body px-3 py-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;">{{ $c[0] }}</div>
                        <div style="background:{{ $c[5] }};border-radius:7px;padding:5px 7px;">
                            <i class="bi {{ $c[2] ?? 'bi-dash' }}" style="font-size:1rem;color:{{ $c[4] }};"></i>
                        </div>
                    </div>
                    <div style="font-size:1.5rem;font-weight:700;color:{{ $c[4] }};line-height:1.1;">{{ $c[1] }}</div>
                    @if($c[3] !== null)
                    <div style="font-size:.75rem;margin-top:4px;">
                        @if($c[3] > 0)<span class="diff-up">▲ +{{ number_format((int)$c[3]) }} vs minggu lalu</span>
                        @elseif($c[3] < 0)<span class="diff-dn">▼ {{ number_format((int)$c[3]) }} vs minggu lalu</span>
                        @else<span class="diff-eq">= sama dengan minggu lalu</span>@endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Periode --}}
    <div class="kard mb-3 px-3 py-2" style="font-size:.82rem;">
        <div class="d-flex gap-4 flex-wrap align-items-center">
            <div>📅 Periode: <strong>{{ $weekStart->format('d M Y') }}</strong> – <strong>{{ $weekEnd->format('d M Y') }}</strong></div>
            <div>📅 Pembanding: <strong>{{ $prevStart->format('d M') }}</strong> – <strong>{{ $prevEnd->format('d M Y') }}</strong></div>
            <div>🎯 Minimal lapor: <strong>{{ $minDays }} hari</strong> (Senin–Sabtu)</div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- Chart trend harian minggu ini --}}
        <div class="col-lg-7">
            <div class="kard">
                <div class="kard-hdr"><span class="kard-title">📈 POB Harian Minggu Ini</span></div>
                <div class="card-body p-3" style="height:200px;">
                    <canvas id="cDaily"></canvas>
                </div>
            </div>
        </div>
        {{-- Chart POB vs prev week --}}
        <div class="col-lg-5">
            <div class="kard">
                <div class="kard-hdr"><span class="kard-title">📊 POB vs Minggu Lalu</span></div>
                <div class="card-body p-3" style="height:200px;">
                    <canvas id="cCompare"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel per perusahaan --}}
    <div class="kard mb-3">
        <div class="kard-hdr">
            <span class="kard-title">📋 Detail per Perusahaan</span>
            <span style="background:#2563eb;color:#fff;border-radius:20px;font-size:.72rem;padding:2px 10px;">{{ $rows->count() }} lapor</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:.8rem;">
                <thead style="background:#f8fafc;font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;">
                    <tr>
                        <th class="px-3 py-2">Perusahaan</th>
                        <th class="text-center">Hari Lapor</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">POB</th>
                        <th class="text-end">vs Lalu</th>
                        <th class="text-end">Manpower</th>
                        <th class="text-end">vs Lalu</th>
                        <th class="text-end">Avg POB/hari</th>
                        <th class="text-center">Laporan Terakhir</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $r)
                <tr>
                    <td class="px-3 fw-semibold">{{ $r->company_name }}</td>
                    <td class="text-center">
                        <span style="font-weight:600;color:{{ $r->days_reported >= $minDays ? '#16a34a' : '#ea580c' }};">
                            {{ $r->days_reported }}/{{ $minDays }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($r->met_minimum)
                        <span class="badge-ok">✔ Cukup</span>
                        @else
                        <span class="badge-no">✖ Kurang</span>
                        @endif
                    </td>
                    <td class="text-end fw-bold" style="color:#2563eb;">{{ number_format((int)($r->total_pob)) }}</td>
                    <td class="text-end">
                        @if($r->pob_diff !== null)
                            @if($r->pob_diff > 0)<span class="diff-up">▲ +{{ number_format((int)($r->pob_diff)) }}</span>
                            @elseif($r->pob_diff < 0)<span class="diff-dn">▼ {{ number_format((int)($r->pob_diff)) }}</span>
                            @else<span class="diff-eq">=</span>@endif
                        @else<span style="color:#cbd5e1;">–</span>@endif
                    </td>
                    <td class="text-end" style="color:#16a34a;">{{ number_format((int)($r->total_mp)) }}</td>
                    <td class="text-end">
                        @if($r->mp_diff !== null)
                            @if($r->mp_diff > 0)<span class="diff-up">▲ +{{ number_format((int)($r->mp_diff)) }}</span>
                            @elseif($r->mp_diff < 0)<span class="diff-dn">▼ {{ number_format((int)($r->mp_diff)) }}</span>
                            @else<span class="diff-eq">=</span>@endif
                        @else<span style="color:#cbd5e1;">–</span>@endif
                    </td>
                    <td class="text-end" style="color:#7c3aed;">{{ $r->avg_pob }}</td>
                    <td class="text-center text-muted" style="font-size:.75rem;">
                        {{ $r->last_report ? \Carbon\Carbon::parse($r->last_report)->format('d M Y') : '-' }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-4 text-muted">Belum ada data untuk minggu ini</td></tr>
                @endforelse
                </tbody>
                @if($rows->count() > 0)
                <tfoot style="background:#f8fafc;font-size:.8rem;font-weight:700;">
                    <tr>
                        <td class="px-3 py-2">TOTAL</td>
                        <td></td><td></td>
                        <td class="text-end" style="color:#2563eb;">{{ number_format((int)($totalPob)) }}</td>
                        <td class="text-end">
                            @if($pobDiff > 0)<span class="diff-up">▲ +{{ number_format((int)($pobDiff)) }}</span>
                            @elseif($pobDiff < 0)<span class="diff-dn">▼ {{ number_format((int)($pobDiff)) }}</span>
                            @else<span class="diff-eq">=</span>@endif
                        </td>
                        <td class="text-end" style="color:#16a34a;">{{ number_format((int)($totalMp)) }}</td>
                        <td class="text-end">
                            @if($mpDiff > 0)<span class="diff-up">▲ +{{ number_format((int)($mpDiff)) }}</span>
                            @elseif($mpDiff < 0)<span class="diff-dn">▼ {{ number_format((int)($mpDiff)) }}</span>
                            @else<span class="diff-eq">=</span>@endif
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Belum lapor --}}
    @if($notReported->count() > 0)
    <div class="kard">
        <div class="kard-hdr">
            <span class="kard-title" style="color:#dc2626;">⚠ Belum Lapor Minggu Ini</span>
            <span style="background:#fee2e2;color:#dc2626;border-radius:20px;font-size:.72rem;padding:2px 10px;">{{ $notReported->count() }}</span>
        </div>
        <div class="card-body px-3 py-2">
            <div class="d-flex flex-wrap gap-2">
                @foreach($notReported as $c)
                <span style="background:#fff;border:1px solid #fecaca;border-radius:20px;font-size:.78rem;padding:3px 12px;color:#dc2626;">
                    {{ $c->name }}
                </span>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ── MONTHLY VIEW ── --}}
    @elseif($view === 'monthly')
    @php
        $pobDiff = $totalPob - $totalPrevPob;
        $mpDiff  = $totalMp  - $totalPrevMp;
    @endphp
    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="kard" style="border-top:3px solid #2563eb;"><div class="card-body px-3 py-3">
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;">Total POB Bulan Ini</div>
            <div style="font-size:1.5rem;font-weight:700;color:#2563eb;">{{ number_format((int)($totalPob)) }}</div>
            <div style="font-size:.75rem;margin-top:4px;">
                @if($pobDiff>0)<span class="diff-up">▲ +{{ number_format((int)($pobDiff)) }} vs bulan lalu</span>
                @elseif($pobDiff<0)<span class="diff-dn">▼ {{ number_format((int)($pobDiff)) }} vs bulan lalu</span>
                @else<span class="diff-eq">= sama dengan bulan lalu</span>@endif
            </div>
        </div></div></div>
        <div class="col-md-4"><div class="kard" style="border-top:3px solid #16a34a;"><div class="card-body px-3 py-3">
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;">Total Manpower</div>
            <div style="font-size:1.5rem;font-weight:700;color:#16a34a;">{{ number_format((int)($totalMp)) }}</div>
            <div style="font-size:.75rem;margin-top:4px;">
                @if($mpDiff>0)<span class="diff-up">▲ +{{ number_format((int)($mpDiff)) }} vs bulan lalu</span>
                @elseif($mpDiff<0)<span class="diff-dn">▼ {{ number_format((int)($mpDiff)) }} vs bulan lalu</span>
                @else<span class="diff-eq">= sama dengan bulan lalu</span>@endif
            </div>
        </div></div></div>
        <div class="col-md-4"><div class="kard" style="border-top:3px solid #7c3aed;"><div class="card-body px-3 py-3">
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;">Periode</div>
            <div style="font-size:1rem;font-weight:700;color:#7c3aed;">{{ $monthStart->format('d M') }} – {{ $monthEnd->format('d M Y') }}</div>
        </div></div></div>
    </div>

    {{-- Chart trend mingguan dalam bulan --}}
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="kard">
                <div class="kard-hdr"><span class="kard-title">📈 Trend per Minggu dalam Bulan</span></div>
                <div class="card-body p-3" style="height:220px;"><canvas id="cMonthWeek"></canvas></div>
            </div>
        </div>
    </div>

    {{-- Tabel per minggu --}}
    <div class="kard mb-3">
        <div class="kard-hdr"><span class="kard-title">📋 Rekap per Minggu</span></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:.82rem;">
                <thead style="background:#f8fafc;font-size:.7rem;color:#94a3b8;text-transform:uppercase;">
                    <tr>
                        <th class="px-3 py-2">Minggu ke</th>
                        <th>Periode</th>
                        <th class="text-end">Total POB</th>
                        <th class="text-end">Total MP</th>
                        <th class="text-center">Hari Data</th>
                        <th class="text-center">Perusahaan</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($weeklyInMonth as $wi => $w)
                <tr>
                    <td class="px-3 fw-semibold">Minggu {{ $wi+1 }}</td>
                    <td class="text-muted" style="font-size:.78rem;">
                        {{ \Carbon\Carbon::parse($w->week_start)->format('d M') }} – {{ \Carbon\Carbon::parse($w->week_end)->format('d M Y') }}
                    </td>
                    <td class="text-end fw-bold" style="color:#2563eb;">{{ number_format((int)($w->total_pob)) }}</td>
                    <td class="text-end" style="color:#16a34a;">{{ number_format((int)($w->total_mp)) }}</td>
                    <td class="text-center">{{ $w->days }} hari</td>
                    <td class="text-center">{{ $w->reporters }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── YEARLY VIEW ── --}}
    @else
    @php
        $pobDiff = $totalPob - $totalPrevPob;
        $mpDiff  = $totalMp  - $totalPrevMp;
    @endphp
    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="kard" style="border-top:3px solid #2563eb;"><div class="card-body px-3 py-3">
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;">Total POB {{ $year }}</div>
            <div style="font-size:1.5rem;font-weight:700;color:#2563eb;">{{ number_format((int)($totalPob)) }}</div>
            <div style="font-size:.75rem;margin-top:4px;">
                @if($pobDiff>0)<span class="diff-up">▲ +{{ number_format((int)($pobDiff)) }} vs {{ $year-1 }}</span>
                @elseif($pobDiff<0)<span class="diff-dn">▼ {{ number_format((int)($pobDiff)) }} vs {{ $year-1 }}</span>
                @else<span class="diff-eq">= sama dengan {{ $year-1 }}</span>@endif
            </div>
        </div></div></div>
        <div class="col-md-4"><div class="kard" style="border-top:3px solid #16a34a;"><div class="card-body px-3 py-3">
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;">Total Manpower {{ $year }}</div>
            <div style="font-size:1.5rem;font-weight:700;color:#16a34a;">{{ number_format((int)($totalMp)) }}</div>
            <div style="font-size:.75rem;margin-top:4px;">
                @if($mpDiff>0)<span class="diff-up">▲ +{{ number_format((int)($mpDiff)) }} vs {{ $year-1 }}</span>
                @elseif($mpDiff<0)<span class="diff-dn">▼ {{ number_format((int)($mpDiff)) }} vs {{ $year-1 }}</span>
                @else<span class="diff-eq">= sama dengan {{ $year-1 }}</span>@endif
            </div>
        </div></div></div>
        <div class="col-md-4"><div class="kard" style="border-top:3px solid #ea580c;"><div class="card-body px-3 py-3">
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;">Rata-rata POB per Bulan</div>
            <div style="font-size:1.5rem;font-weight:700;color:#ea580c;">{{ $monthlyRows->count() > 0 ? number_format((int)($totalPob) / $monthlyRows->count(), 0) : 0 }}</div>
        </div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="kard">
                <div class="kard-hdr"><span class="kard-title">📈 Trend POB per Bulan — {{ $year }} vs {{ $year-1 }}</span></div>
                <div class="card-body p-3" style="height:240px;"><canvas id="cYearly"></canvas></div>
            </div>
        </div>
    </div>

    <div class="kard">
        <div class="kard-hdr"><span class="kard-title">📋 Rekap per Bulan {{ $year }}</span></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:.82rem;">
                <thead style="background:#f8fafc;font-size:.7rem;color:#94a3b8;text-transform:uppercase;">
                    <tr>
                        <th class="px-3 py-2">Bulan</th>
                        <th class="text-end">POB {{ $year }}</th>
                        <th class="text-end">vs {{ $year-1 }}</th>
                        <th class="text-end">MP {{ $year }}</th>
                        <th class="text-end">vs {{ $year-1 }}</th>
                        <th class="text-center">Hari Data</th>
                        <th class="text-center">Perusahaan</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($monthlyRows as $mr)
                @php
                    $pd = $mr->total_pob - $mr->prev_pob;
                    $md = $mr->total_mp  - $mr->prev_mp;
                @endphp
                <tr>
                    <td class="px-3 fw-semibold">{{ $mr->label }}</td>
                    <td class="text-end fw-bold" style="color:#2563eb;">{{ number_format((int)($mr->total_pob)) }}</td>
                    <td class="text-end">
                        @if($pd>0)<span class="diff-up">▲ +{{ number_format((int)($pd)) }}</span>
                        @elseif($pd<0)<span class="diff-dn">▼ {{ number_format((int)($pd)) }}</span>
                        @else<span class="diff-eq">=</span>@endif
                    </td>
                    <td class="text-end" style="color:#16a34a;">{{ number_format((int)($mr->total_mp)) }}</td>
                    <td class="text-end">
                        @if($md>0)<span class="diff-up">▲ +{{ number_format((int)($md)) }}</span>
                        @elseif($md<0)<span class="diff-dn">▼ {{ number_format((int)($md)) }}</span>
                        @else<span class="diff-eq">=</span>@endif
                    </td>
                    <td class="text-center">{{ $mr->days }}</td>
                    <td class="text-center">{{ $mr->reporters }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data tahun ini</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

</main>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family="'Segoe UI',sans-serif";
Chart.defaults.color='#94a3b8';

@if($view === 'weekly')
@php
    $dayLabels = $dailyData->map(fn($d) => \Carbon\Carbon::parse($d->day)->format('D, d M'))->values()->toArray();
    $dayPob    = $dailyData->map(fn($d) => (int)$d->pob)->values()->toArray();
    $dayMp     = $dailyData->map(fn($d) => (int)$d->mp)->values()->toArray();
    $top10     = $rows->sortByDesc('total_pob')->take(10);
    $cmpLabels = $top10->map(fn($r) => strlen($r->company_name)>18 ? substr($r->company_name,0,16).'…' : $r->company_name)->values()->toArray();
    $cmpCurr   = $top10->map(fn($r) => $r->total_pob)->values()->toArray();
    $cmpPrev   = $top10->map(fn($r) => $r->prev_pob ?? 0)->values()->toArray();
@endphp
new Chart(document.getElementById('cDaily'),{
    type:'bar',
    data:{
        labels:{!! json_encode($dayLabels) !!},
        datasets:[
            {label:'POB',data:{!! json_encode($dayPob) !!},backgroundColor:'rgba(37,99,235,.75)',borderRadius:5},
            {label:'Manpower',data:{!! json_encode($dayMp) !!},backgroundColor:'rgba(22,163,74,.6)',borderRadius:5},
        ]
    },
    options:{responsive:true,maintainAspectRatio:false,
        plugins:{legend:{position:'top',labels:{usePointStyle:true,boxHeight:6}}},
        scales:{x:{grid:{display:false},ticks:{font:{size:10}}},y:{grid:{color:'rgba(0,0,0,.04)'},beginAtZero:true}}
    }
});
new Chart(document.getElementById('cCompare'),{
    type:'bar',
    data:{
        labels:{!! json_encode($cmpLabels) !!},
        datasets:[
            {label:'Minggu Ini',data:{!! json_encode($cmpCurr) !!},backgroundColor:'rgba(37,99,235,.8)',borderRadius:4},
            {label:'Minggu Lalu',data:{!! json_encode($cmpPrev) !!},backgroundColor:'rgba(148,163,184,.5)',borderRadius:4},
        ]
    },
    options:{responsive:true,maintainAspectRatio:false,
        plugins:{legend:{position:'top',labels:{usePointStyle:true,boxHeight:6}}},
        scales:{x:{grid:{display:false},ticks:{font:{size:9},maxRotation:30}},y:{grid:{color:'rgba(0,0,0,.04)'},beginAtZero:true}}
    }
});
@endif

@if($view === 'monthly')
@php
    $mwLabels = $weeklyInMonth->values()->map(fn($w,$i) => 'Minggu '.($i+1))->toArray();
    $mwPob    = $weeklyInMonth->map(fn($w) => (int)$w->total_pob)->values()->toArray();
    $mwMp     = $weeklyInMonth->map(fn($w) => (int)$w->total_mp)->values()->toArray();
@endphp
new Chart(document.getElementById('cMonthWeek'),{
    type:'line',
    data:{
        labels:{!! json_encode($mwLabels) !!},
        datasets:[
            {label:'POB',data:{!! json_encode($mwPob) !!},borderColor:'#2563eb',backgroundColor:'rgba(37,99,235,.07)',tension:.4,fill:true,pointRadius:4,borderWidth:2},
            {label:'Manpower',data:{!! json_encode($mwMp) !!},borderColor:'#16a34a',backgroundColor:'rgba(22,163,74,.07)',tension:.4,fill:true,pointRadius:4,borderWidth:2},
        ]
    },
    options:{responsive:true,maintainAspectRatio:false,
        plugins:{legend:{position:'top',labels:{usePointStyle:true,boxHeight:6}}},
        scales:{x:{grid:{display:false}},y:{grid:{color:'rgba(0,0,0,.04)'},beginAtZero:false}}
    }
});
@endif

@if($view === 'yearly')
@php
    $yrLabels   = $monthlyRows->pluck('label')->toArray();
    $yrCurr     = $monthlyRows->pluck('total_pob')->toArray();
    $yrPrev     = $monthlyRows->pluck('prev_pob')->toArray();
    $yrMpCurr   = $monthlyRows->pluck('total_mp')->toArray();
@endphp
new Chart(document.getElementById('cYearly'),{
    type:'line',
    data:{
        labels:{!! json_encode($yrLabels) !!},
        datasets:[
            {label:'POB {{ $year }}',data:{!! json_encode($yrCurr) !!},borderColor:'#2563eb',backgroundColor:'rgba(37,99,235,.07)',tension:.4,fill:true,pointRadius:4,borderWidth:2},
            {label:'POB {{ $year-1 }}',data:{!! json_encode($yrPrev) !!},borderColor:'#94a3b8',backgroundColor:'rgba(148,163,184,.05)',tension:.4,fill:true,pointRadius:3,borderWidth:1.5,borderDash:[4,3]},
            {label:'MP {{ $year }}',data:{!! json_encode($yrMpCurr) !!},borderColor:'#16a34a',backgroundColor:'rgba(22,163,74,.05)',tension:.4,fill:false,pointRadius:3,borderWidth:1.5},
        ]
    },
    options:{responsive:true,maintainAspectRatio:false,
        interaction:{mode:'index',intersect:false},
        plugins:{legend:{position:'top',labels:{usePointStyle:true,boxHeight:6}}},
        scales:{x:{grid:{display:false}},y:{grid:{color:'rgba(0,0,0,.04)'},beginAtZero:false}}
    }
});
@endif
</script>
@endpush