@extends('layouts.app')
@section('title', $company ? 'Edit Perusahaan' : 'Tambah Perusahaan')
@section('sidebar-nav')
@include('partials.sidebar')
@endsection

@section('content')
<div class="mb-3">
        <a href="{{ route('companies.index') }}" class="text-muted text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Perusahaan
        </a>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:14px;max-width:560px;">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-1">{{ $company ? 'Edit Perusahaan' : 'Tambah Perusahaan Baru' }}</h5>
            <p class="text-muted small mb-4">{{ $company ? $company->name : 'Isi data perusahaan kontraktor' }}</p>

            @if($errors->any())
            <div class="alert alert-danger border-0 py-2 px-3 mb-3" style="border-radius:8px;font-size:.85rem;">
                <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
            @endif

            <form method="POST" action="{{ $company ? route('companies.update',$company) : route('companies.store') }}">
                @csrf
                @if($company) @method('PUT') @endif

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Perusahaan <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                        value="{{ old('name', $company?->name) }}"
                        placeholder="PT Contoh Nama Perusahaan" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Tipe <span class="text-danger">*</span></label>
                    <select name="type" class="form-select" required>
                        @foreach(['contractor'=>'Kontraktor','subcontractor'=>'Sub Kontraktor','vendor'=>'Vendor','other'=>'Lainnya'] as $val => $lbl)
                        <option value="{{ $val }}" {{ old('type',$company?->type ?? 'contractor') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                            {{ old('is_active', $company ? $company->is_active : true) ? 'checked' : '' }}>
                        <label class="form-check-label small fw-semibold" for="is_active">Perusahaan Aktif</label>
                        <div class="text-muted" style="font-size:.75rem;">Perusahaan nonaktif tidak akan muncul di form mitra</div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-semibold px-4" style="border-radius:8px;">
                        <i class="bi bi-check-circle me-1"></i>{{ $company ? 'Simpan Perubahan' : 'Tambah Perusahaan' }}
                    </button>
                    <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary px-4" style="border-radius:8px;">Batal</a>
                </div>
            </form>
        </div>
    </div>


@endsection
