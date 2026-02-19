@extends('layouts.app')

@section('title', 'Login Dashboard POB')

@section('content')
<div class="min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #1a3c5e 0%, #0d2137 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="text-center mb-4">
                    <i class="bi bi-bar-chart-fill text-white" style="font-size:3rem;"></i>
                    <h4 class="text-white fw-bold mt-2">Dashboard POB</h4>
                    <p class="text-white-50">PT Sulawesi Cahaya Mineral</p>
                </div>
                <div class="card border-0 shadow-lg" style="border-radius:16px;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">Masuk ke Dashboard</h5>
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}" autofocus required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="remember" id="remember">
                                <label class="form-check-label" for="remember">Ingat saya</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" style="background:#1a3c5e; border-color:#1a3c5e;">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                            </button>
                        </form>
                        <hr>
                        <p class="text-center mb-0">
                            <a href="{{ route('form.index') }}" class="text-decoration-none small">
                                <i class="bi bi-arrow-left me-1"></i>Kembali ke Form Input Mitra
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
