<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Login Admin – PT SCM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body style="background:#1a3c5e;min-height:100vh;display:flex;align-items:center;">
<div class="container"><div class="row justify-content-center">
<div class="col-md-4">
    <div class="text-center mb-4 text-white">
        <h4 class="fw-bold">SCM Nickel</h4>
        <small style="opacity:.6;">POB & Manpower System</small>
    </div>
    <div class="card border-0 shadow-lg" style="border-radius:14px;">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-1">Login Admin</h5>
            <p class="text-muted small mb-3">PT Sulawesi Cahaya Mineral</p>
            @if($errors->any())
            <div class="alert alert-danger py-2 px-3 small">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100 fw-semibold" style="background:#1a3c5e;border-color:#1a3c5e;border-radius:8px;">
                    Masuk ke Dashboard
                </button>
            </form>
        </div>
    </div>
    <div class="text-center mt-3">
        <a href="{{ route('form.index') }}" class="text-white-50 small">← Kembali ke Form Mitra</a>
    </div>
</div>
</div></div>
</body>
</html>
