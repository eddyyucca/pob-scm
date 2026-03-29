@extends('layouts.app')
@section('title','Laporan POB')
@section('sidebar-nav')
@include('partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0">Laporan POB Harian</h5>
            <small class="text-muted">Lihat, edit, dan hapus data laporan dari mitra</small>
        </div>
        <a href="{{ route('dashboard.export') }}?from={{ $from }}&to={{ $to }}" class="btn btn-sm btn-outline-success" style="border-radius:20px;">
            <i class="bi bi-download me-1"></i>Export CSV
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 py-2 px-3 mb-3" style="border-radius:8px;font-size:.85rem;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger border-0 py-2 px-3 mb-3" style="border-radius:8px;font-size:.85rem;">
        <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
    </div>
    @endif

    {{-- Filter --}}
    <div class="card border-0 shadow-sm mb-3 px-3 py-2" style="border-radius:10px;">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <label class="small fw-semibold text-muted mb-0">Dari:</label>
            <input type="date" name="from" class="form-control form-control-sm" style="width:auto;" value="{{ $from }}">
            <label class="small fw-semibold text-muted mb-0">Sampai:</label>
            <input type="date" name="to" class="form-control form-control-sm" style="width:auto;" value="{{ $to }}" max="{{ now()->toDateString() }}">
            <select name="company_id" class="form-select form-select-sm" style="width:210px;">
                <option value="">Semua Perusahaan</option>
                @foreach($companies as $c)
                <option value="{{ $c->id }}" {{ $companyId==$c->id?'selected':'' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            <input type="text" name="search" class="form-control form-control-sm" style="width:180px;"
                placeholder="Cari perusahaan..." value="{{ $search }}">
            <button class="btn btn-sm btn-primary" style="border-radius:8px;">Tampilkan</button>
            <a href="{{ route('pob-entries.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">Reset</a>
            <div class="ms-auto text-muted small">
                Total POB: <strong style="color:#2563eb;">{{ number_format($total_pob) }}</strong>
            </div>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:.82rem;">
                <thead style="background:#f8fafc;font-size:.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;">
                    <tr>
                        <th class="px-3 py-2">#</th>
                        <th>Tanggal</th>
                        <th>Perusahaan</th>
                        <th class="text-end">POB</th>
                        <th class="text-end">Manpower</th>
                        <th class="text-end">Rasio</th>
                        <th class="text-center">Karyawan</th>
                        <th>Pelapor</th>
                        <th>Kontak WA</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($entries as $i => $e)
                @php
                    $ratio = $e->total_manpower > 0 ? round($e->total_pob/$e->total_manpower*100,1) : 0;
                    $rc    = $ratio>=80?'#16a34a':($ratio>=50?'#ea580c':'#94a3b8');
                    $ec    = $e->employees()->count();
                @endphp
                <tr>
                    <td class="px-3" style="color:#cbd5e1;font-size:.72rem;">{{ $entries->firstItem() + $i }}</td>
                    <td class="fw-semibold" style="white-space:nowrap;">{{ $e->date->format('d M Y') }}</td>
                    <td style="max-width:180px;">{{ $e->company->name ?? '-' }}</td>
                    <td class="text-end fw-bold" style="color:#2563eb;">{{ number_format($e->total_pob) }}</td>
                    <td class="text-end" style="color:#16a34a;">{{ number_format($e->total_manpower) }}</td>
                    <td class="text-end">
                        <span style="background:{{ $rc }};color:#fff;border-radius:20px;font-size:.7rem;padding:2px 8px;font-weight:600;">{{ $ratio }}%</span>
                    </td>
                    <td class="text-center">
                        @if($ec > 0)
                        <a href="{{ route('pob-entries.show', $e) }}"
                           style="background:#dcfce7;color:#16a34a;border-radius:20px;font-size:.72rem;padding:2px 9px;text-decoration:none;font-weight:600;">
                            {{ $ec }} orang
                        </a>
                        @else
                        <span style="color:#fbbf24;font-size:.75rem;">–</span>
                        @endif
                    </td>
                    <td class="text-muted" style="font-size:.78rem;max-width:130px;">{{ $e->informed_by ?? '-' }}</td>
                    <td class="text-muted" style="font-size:.78rem;">{{ $e->contact_wa ?? '-' }}</td>
                    <td class="text-center pe-3">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="{{ route('pob-entries.show', $e) }}" class="btn btn-sm btn-outline-info" style="border-radius:6px;padding:3px 9px;font-size:.75rem;" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('pob-entries.edit', $e) }}" class="btn btn-sm btn-outline-primary" style="border-radius:6px;padding:3px 9px;font-size:.75rem;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('pob-entries.destroy', $e) }}"
                                  onsubmit="return confirm('Hapus laporan ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" style="border-radius:6px;padding:3px 9px;font-size:.75rem;" title="Hapus">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center py-5 text-muted">
                    <i class="bi bi-journal-x" style="font-size:2rem;display:block;opacity:.3;margin-bottom:8px;"></i>
                    Tidak ada laporan untuk periode ini
                </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
        <div class="px-3 py-2 border-top">{{ $entries->appends(request()->all())->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>


@endsection
