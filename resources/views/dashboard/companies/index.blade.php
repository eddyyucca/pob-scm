@extends('layouts.app')
@section('title','Manajemen Perusahaan')
@section('content')
<div class="d-flex">
@include('partials.sidebar')
<main style="flex:1;padding:22px;background:#f0f4f8;">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0">Manajemen Perusahaan</h5>
            <small class="text-muted">Kelola daftar perusahaan kontraktor</small>
        </div>
        <a href="{{ route('companies.create') }}" class="btn btn-primary btn-sm" style="border-radius:8px;">
            <i class="bi bi-plus-circle me-1"></i>Tambah Perusahaan
        </a>
    </div>

    {{-- Alert --}}
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
        <form method="GET" class="d-flex gap-3 align-items-center flex-wrap">
            <input type="text" name="search" class="form-control form-control-sm" style="width:260px;"
                placeholder="Cari nama perusahaan..." value="{{ $search }}">
            <select name="status" class="form-select form-select-sm" style="width:auto;">
                <option value="all"      {{ $status==='all'?'selected':'' }}>Semua Status</option>
                <option value="active"   {{ $status==='active'?'selected':'' }}>Aktif</option>
                <option value="inactive" {{ $status==='inactive'?'selected':'' }}>Nonaktif</option>
            </select>
            <button class="btn btn-sm btn-primary" style="border-radius:8px;">Cari</button>
            <a href="{{ route('companies.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">Reset</a>
            <span class="ms-auto text-muted small">{{ $companies->total() }} perusahaan</span>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:.83rem;">
                <thead style="background:#f8fafc;font-size:.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;">
                    <tr>
                        <th class="px-3 py-2">#</th>
                        <th>Nama Perusahaan</th>
                        <th>Tipe</th>
                        <th class="text-center">Laporan</th>
                        <th class="text-center">Karyawan</th>
                        <th class="text-center">Kontak WA</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($companies as $i => $c)
                <tr>
                    <td class="px-3" style="color:#cbd5e1;font-size:.72rem;">{{ $companies->firstItem() + $i }}</td>
                    <td>
                        <div class="fw-semibold">{{ $c->name }}</div>
                        @if($c->slug)<div style="font-size:.72rem;color:#94a3b8;">{{ $c->slug }}</div>@endif
                    </td>
                    <td>
                        <span style="background:#eff6ff;color:#2563eb;border-radius:20px;font-size:.72rem;padding:2px 9px;">
                            {{ $c->type ?? 'contractor' }}
                        </span>
                    </td>
                    <td class="text-center fw-semibold" style="color:#2563eb;">{{ number_format($c->total_reports) }}</td>
                    <td class="text-center" style="color:#16a34a;">{{ number_format($c->total_employees) }}</td>
                    <td class="text-center" style="color:#7c3aed;">{{ $c->total_contacts }}</td>
                    <td class="text-center">
                        <form method="POST" action="{{ route('companies.toggle', $c) }}" style="display:inline;">
                            @csrf @method('PATCH')
                            <button class="border-0 p-0" style="background:none;cursor:pointer;">
                                @if($c->is_active)
                                <span style="background:#dcfce7;color:#16a34a;border-radius:20px;font-size:.72rem;padding:3px 10px;font-weight:600;">Aktif</span>
                                @else
                                <span style="background:#fee2e2;color:#dc2626;border-radius:20px;font-size:.72rem;padding:3px 10px;font-weight:600;">Nonaktif</span>
                                @endif
                            </button>
                        </form>
                    </td>
                    <td class="text-center pe-3">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="{{ route('companies.edit', $c) }}" class="btn btn-sm btn-outline-primary" style="border-radius:6px;padding:3px 9px;font-size:.75rem;">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('companies.destroy', $c) }}"
                                  onsubmit="return confirm('Hapus perusahaan \'{{ addslashes($c->name) }}\'?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" style="border-radius:6px;padding:3px 9px;font-size:.75rem;">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-building" style="font-size:2rem;display:block;opacity:.3;margin-bottom:8px;"></i>
                    Belum ada perusahaan
                </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($companies->hasPages())
        <div class="px-3 py-2 border-top">{{ $companies->appends(request()->all())->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>

</main>
</div>
@endsection
