@extends('layouts.app')
@section('title','Data Karyawan POB')
@section('content')
<div class="d-flex" style="min-height:100vh;">

{{-- Sidebar --}}
<nav class="d-flex flex-column p-3" style="width:220px;min-height:100vh;background:#1a3c5e;color:#fff;flex-shrink:0;">
    <div class="mb-4 mt-2 text-center">
        <div style="font-size:1.05rem;font-weight:700;">&#x26CF; SCM Nickel</div>
        <div style="font-size:.72rem;opacity:.55;">POB & Manpower System</div>
    </div>
    <a href="{{ route('dashboard') }}" class="nav-link text-white py-2 px-3 rounded mb-1" style="opacity:.7;"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
    <a href="{{ route('employees.index') }}" class="nav-link text-white py-2 px-3 rounded mb-1" style="background:rgba(255,255,255,.15);"><i class="bi bi-people me-2"></i>Data Karyawan</a>
    <a href="{{ route('employees.upload') }}" class="nav-link text-white py-2 px-3 rounded mb-1" style="opacity:.7;"><i class="bi bi-person-plus me-2"></i>Upload Karyawan</a>
    <a href="{{ route('dashboard.import') }}" class="nav-link text-white py-2 px-3 rounded mb-1" style="opacity:.7;"><i class="bi bi-upload me-2"></i>Import POB</a>
    <div class="mt-auto pt-3 border-top border-secondary">
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button class="btn btn-sm btn-outline-light w-100"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
        </form>
    </div>
</nav>

<main class="flex-grow-1 p-4" style="background:#f0f4f8;overflow-x:hidden;">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">Data Karyawan Onsite (POB)</h4>
            <small class="text-muted">Daftar karyawan yang sedang berada di area operasi</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('employees.upload') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-person-plus me-1"></i>Upload Karyawan
            </a>
            <a href="{{ route('employees.export', request()->all()) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-download me-1"></i>Export CSV
            </a>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card border-0 shadow-sm mb-4 p-3" style="border-radius:10px;">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small fw-semibold mb-1">Tanggal</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" max="{{ now()->toDateString() }}">
            </div>
            <div class="col-auto">
                <label class="form-label small fw-semibold mb-1">Perusahaan</label>
                <select name="company_id" class="form-select form-select-sm" style="min-width:200px;">
                    <option value="">Semua Perusahaan</option>
                    @foreach($companies as $c)
                    <option value="{{ $c->id }}" {{ $companyId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small fw-semibold mb-1">Departemen</label>
                <select name="department" class="form-select form-select-sm" style="min-width:150px;">
                    <option value="">Semua Dept</option>
                    @foreach($departments as $d)
                    <option value="{{ $d }}" {{ $dept === $d ? 'selected' : '' }}>{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small fw-semibold mb-1">Tipe</label>
                <select name="employee_type" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="employee" {{ $type==='employee' ? 'selected' : '' }}>Karyawan</option>
                    <option value="visitor" {{ $type==='visitor' ? 'selected' : '' }}>Visitor</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small fw-semibold mb-1">Cari</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Nama / No ID..." value="{{ $search }}" style="min-width:150px;">
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary">Tampilkan</button>
                <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
            </div>
        </form>
    </div>

    {{-- Stat cards --}}
    @if($summary)
    <div class="row g-3 mb-4">
        @php
        $statCards = [
            ['label'=>'Total Karyawan','val'=>number_format($summary->total),'icon'=>'bi-people-fill','color'=>'#0d6efd'],
            ['label'=>'Karyawan Tetap','val'=>number_format($summary->total_employee),'icon'=>'bi-person-badge','color'=>'#198754'],
            ['label'=>'Visitor/Tamu','val'=>number_format($summary->total_visitor),'icon'=>'bi-person-walking','color'=>'#fd7e14'],
            ['label'=>'Departemen','val'=>number_format($summary->total_dept),'icon'=>'bi-diagram-3','color'=>'#6f42c1'],
            ['label'=>'Perusahaan','val'=>number_format($summary->total_company),'icon'=>'bi-building','color'=>'#dc3545'],
        ];
        @endphp
        @foreach($statCards as $c)
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius:12px;">
                <i class="bi {{ $c['icon'] }} mb-1" style="font-size:1.4rem;color:{{ $c['color'] }};"></i>
                <div style="font-size:1.3rem;font-weight:700;color:{{ $c['color'] }};">{{ $c['val'] }}</div>
                <div style="font-size:.72rem;color:#64748b;">{{ $c['label'] }}</div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Tabel --}}
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3">
            <h6 class="fw-bold mb-0">
                Daftar Karyawan — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
            </h6>
            <span class="badge bg-primary">{{ $employees->total() }} orang</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle" style="font-size:.84rem;">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th class="px-3 py-2">#</th>
                            <th class="py-2">Tipe ID</th>
                            <th class="py-2">No ID</th>
                            <th class="py-2">Nama</th>
                            <th class="py-2">Jabatan</th>
                            <th class="py-2">Departemen</th>
                            <th class="py-2">Tipe</th>
                            <th class="py-2">Perusahaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $i => $emp)
                        <tr>
                            <td class="px-3 text-muted">{{ $employees->firstItem() + $i }}</td>
                            <td>
                                <span class="badge {{ $emp->id_type === 'minepermit' ? 'bg-primary' : 'bg-warning text-dark' }}" style="font-size:.7rem;">
                                    {{ $emp->id_type === 'minepermit' ? 'MinePermit' : 'KTP' }}
                                </span>
                            </td>
                            <td class="font-monospace text-muted small">{{ $emp->id_number }}</td>
                            <td class="fw-semibold">{{ $emp->name }}</td>
                            <td class="text-muted">{{ $emp->position ?? '-' }}</td>
                            <td>
                                @if($emp->department)
                                <span class="badge bg-light text-dark border" style="font-size:.72rem;">{{ $emp->department }}</span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $emp->employee_type === 'visitor' ? 'bg-warning text-dark' : 'bg-success' }}" style="font-size:.7rem;">
                                    {{ $emp->employee_type === 'visitor' ? 'Visitor' : 'Karyawan' }}
                                </span>
                            </td>
                            <td class="text-muted small">{{ Str::limit($emp->company->name ?? '-', 30) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                                Belum ada data karyawan untuk tanggal ini.<br>
                                <a href="{{ route('employees.upload') }}" class="btn btn-sm btn-primary mt-2">Upload Sekarang</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($employees->hasPages())
            <div class="px-3 py-2 border-top">
                {{ $employees->appends(request()->all())->links('pagination::bootstrap-5') }}
            </div>
            @endif
        </div>
    </div>
</main>
</div>
@endsection
