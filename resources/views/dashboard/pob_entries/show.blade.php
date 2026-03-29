@extends('layouts.app')
@section('title','Detail Laporan POB')
@section('content')
<div class="d-flex">
@include('partials.sidebar')
<main style="flex:1;padding:22px;background:#f0f4f8;overflow-x:hidden;">

    <div class="mb-3 d-flex justify-content-between align-items-center">
        <a href="{{ route('pob-entries.index') }}" class="text-muted text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Laporan
        </a>
        <a href="{{ route('pob-entries.edit', $pobEntry) }}" class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
            <i class="bi bi-pencil me-1"></i>Edit Laporan
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 py-2 px-3 mb-3" style="border-radius:8px;font-size:.85rem;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    </div>
    @endif

    {{-- Info laporan --}}
    <div class="row g-3 mb-3">
        @php
            $ratio = $pobEntry->total_manpower > 0
                ? round($pobEntry->total_pob/$pobEntry->total_manpower*100,1) : 0;
        @endphp
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4" style="border-radius:12px;">
                <h6 class="fw-bold mb-3">{{ $pobEntry->company->name }}</h6>
                <div class="row g-3">
                    <div class="col-4 text-center">
                        <div style="font-size:2rem;font-weight:700;color:#2563eb;line-height:1;">{{ $pobEntry->total_pob }}</div>
                        <div style="font-size:.72rem;color:#64748b;">Total POB</div>
                    </div>
                    <div class="col-4 text-center">
                        <div style="font-size:2rem;font-weight:700;color:#16a34a;line-height:1;">{{ $pobEntry->total_manpower }}</div>
                        <div style="font-size:.72rem;color:#64748b;">Total Manpower</div>
                    </div>
                    <div class="col-4 text-center">
                        <div style="font-size:2rem;font-weight:700;color:#ea580c;line-height:1;">{{ $ratio }}%</div>
                        <div style="font-size:.72rem;color:#64748b;">Rasio POB/MP</div>
                    </div>
                </div>
                <hr class="my-3">
                <div class="row g-2" style="font-size:.83rem;">
                    <div class="col-6"><span class="text-muted">Tanggal:</span> <strong>{{ $pobEntry->date->format('d M Y') }}</strong></div>
                    <div class="col-6"><span class="text-muted">Pelapor:</span> {{ $pobEntry->informed_by ?? '-' }}</div>
                    <div class="col-6"><span class="text-muted">Kontak WA:</span> {{ $pobEntry->contact_wa ?? '-' }}</div>
                    <div class="col-6"><span class="text-muted">Input:</span> {{ $pobEntry->created_at->format('d M Y H:i') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 text-center h-100" style="border-radius:12px;">
                <div style="font-size:3rem;font-weight:700;color:#7c3aed;line-height:1;">{{ $employees->total() }}</div>
                <div style="font-size:.8rem;color:#64748b;margin-bottom:16px;">Karyawan Tercatat</div>
                @if($pobEntry->total_pob != $employees->total())
                <div class="alert alert-warning py-1 px-2 mb-0" style="border-radius:8px;font-size:.75rem;">
                    POB laporan ({{ $pobEntry->total_pob }}) ≠ data karyawan ({{ $employees->total() }})
                </div>
                @else
                <div style="background:#dcfce7;color:#16a34a;border-radius:8px;font-size:.75rem;padding:6px;">
                    ✔ Jumlah sesuai laporan
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Tabel karyawan --}}
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3 pb-2 px-3">
            <h6 class="fw-bold mb-0 small">Daftar Karyawan Onsite</h6>
            <span style="background:#2563eb;color:#fff;border-radius:20px;font-size:.72rem;padding:2px 10px;">{{ $employees->total() }} orang</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:.82rem;">
                <thead style="background:#f8fafc;font-size:.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;">
                    <tr>
                        <th class="px-3 py-2">#</th>
                        <th>Tipe ID</th>
                        <th>No ID</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Departemen</th>
                        <th class="text-center">Tipe</th>
                        <th class="text-center pe-3">Hapus</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($employees as $i => $emp)
                <tr>
                    <td class="px-3" style="color:#cbd5e1;font-size:.72rem;">{{ $employees->firstItem() + $i }}</td>
                    <td>
                        <span style="background:{{ $emp->id_type==='minepermit'?'#eff6ff':'#fffbeb' }};color:{{ $emp->id_type==='minepermit'?'#2563eb':'#d97706' }};border-radius:20px;font-size:.7rem;padding:2px 8px;font-weight:600;">
                            {{ $emp->id_type==='minepermit'?'MinePermit':'KTP' }}
                        </span>
                    </td>
                    <td class="font-monospace text-muted" style="font-size:.75rem;">{{ $emp->id_number }}</td>
                    <td class="fw-semibold">{{ $emp->name }}</td>
                    <td class="text-muted">{{ $emp->position ?? '-' }}</td>
                    <td>
                        @if($emp->department)
                        <span style="background:#f1f5f9;color:#475569;border-radius:20px;font-size:.7rem;padding:2px 8px;">{{ $emp->department }}</span>
                        @else<span class="text-muted">-</span>@endif
                    </td>
                    <td class="text-center">
                        <span style="background:{{ $emp->employee_type==='visitor'?'#fff7ed':'#f0fdf4' }};color:{{ $emp->employee_type==='visitor'?'#ea580c':'#16a34a' }};border-radius:20px;font-size:.7rem;padding:2px 8px;font-weight:600;">
                            {{ $emp->employee_type==='visitor'?'Visitor':'Karyawan' }}
                        </span>
                    </td>
                    <td class="text-center pe-3">
                        <form method="POST" action="{{ route('pob-entries.employee.destroy', $emp) }}"
                              onsubmit="return confirm('Hapus {{ addslashes($emp->name) }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" style="border-radius:6px;padding:3px 9px;font-size:.75rem;">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-people" style="font-size:2rem;display:block;opacity:.3;margin-bottom:8px;"></i>
                    Belum ada data karyawan untuk laporan ini
                </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
        <div class="px-3 py-2 border-top">{{ $employees->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>

</main>
</div>
@endsection
