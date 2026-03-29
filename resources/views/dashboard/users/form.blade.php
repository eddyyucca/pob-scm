@extends('layouts.app')
@section('title', $user ? 'Edit User' : 'Tambah User')
@section('sidebar-nav')
@include('partials.sidebar')
@endsection

@section('content')
<div class="mb-3">
        <a href="{{ route('users.index') }}" class="text-muted text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar User
        </a>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:14px;max-width:520px;">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-1">{{ $user ? 'Edit User' : 'Tambah User Baru' }}</h5>
            <p class="text-muted small mb-4">{{ $user ? $user->email : 'Buat akun admin untuk mengakses dashboard' }}</p>

            @if($errors->any())
            <div class="alert alert-danger border-0 py-2 px-3 mb-3" style="border-radius:8px;font-size:.85rem;">
                <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
            @endif

            <form method="POST" action="{{ $user ? route('users.update',$user) : route('users.store') }}">
                @csrf
                @if($user) @method('PUT') @endif

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                        value="{{ old('name', $user?->name) }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control"
                        value="{{ old('email', $user?->email) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">
                        Password {{ $user ? '(kosongkan jika tidak diubah)' : '' }}
                        @if(!$user)<span class="text-danger">*</span>@endif
                    </label>
                    <input type="password" name="password" class="form-control"
                        {{ !$user ? 'required' : '' }} placeholder="{{ $user ? '••••••••' : '' }}">
                </div>

                @if(!$user)
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Konfirmasi Password <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                @else
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>
                @endif

                <div class="mb-4">
                    <label class="form-label fw-semibold small">Role <span class="text-danger">*</span></label>
                    <select name="role" class="form-select" required>
                        <option value="admin"  {{ old('role',$user?->role ?? 'admin') ==='admin'  ?'selected':'' }}>Admin — Akses penuh</option>
                        <option value="viewer" {{ old('role',$user?->role) ==='viewer' ?'selected':'' }}>Viewer — Hanya lihat data</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-semibold px-4" style="border-radius:8px;">
                        <i class="bi bi-check-circle me-1"></i>{{ $user ? 'Simpan Perubahan' : 'Buat User' }}
                    </button>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary px-4" style="border-radius:8px;">Batal</a>
                </div>
            </form>
        </div>
    </div>


@endsection
