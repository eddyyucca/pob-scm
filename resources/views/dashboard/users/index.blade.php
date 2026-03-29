@extends('layouts.app')
@section('title','Manajemen User')
@section('content')
<div class="d-flex">
@include('partials.sidebar')
<main style="flex:1;padding:22px;background:#f0f4f8;">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold mb-0">Manajemen User</h5>
            <small class="text-muted">Kelola akun admin yang dapat mengakses dashboard</small>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm" style="border-radius:8px;">
            <i class="bi bi-person-plus me-1"></i>Tambah User
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

    <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:.83rem;">
                <thead style="background:#f8fafc;font-size:.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;">
                    <tr>
                        <th class="px-3 py-2">#</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th class="text-center">Role</th>
                        <th class="text-center">Dibuat</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($users as $i => $u)
                <tr>
                    <td class="px-3" style="color:#cbd5e1;font-size:.72rem;">{{ $i+1 }}</td>
                    <td>
                        <div class="fw-semibold d-flex align-items-center gap-2">
                            <div style="width:32px;height:32px;border-radius:50%;background:#1a3c5e;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;flex-shrink:0;">
                                {{ strtoupper(substr($u->name,0,1)) }}
                            </div>
                            {{ $u->name }}
                            @if($u->id === auth()->id())
                            <span style="background:#eff6ff;color:#2563eb;border-radius:20px;font-size:.68rem;padding:1px 7px;">Anda</span>
                            @endif
                        </div>
                    </td>
                    <td class="text-muted">{{ $u->email }}</td>
                    <td class="text-center">
                        @if($u->role === 'admin')
                        <span style="background:#faf5ff;color:#7c3aed;border-radius:20px;font-size:.72rem;padding:3px 10px;font-weight:600;">Admin</span>
                        @else
                        <span style="background:#f1f5f9;color:#64748b;border-radius:20px;font-size:.72rem;padding:3px 10px;font-weight:600;">Viewer</span>
                        @endif
                    </td>
                    <td class="text-center text-muted" style="font-size:.78rem;">{{ $u->created_at->format('d M Y') }}</td>
                    <td class="text-center pe-3">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="{{ route('users.edit', $u) }}" class="btn btn-sm btn-outline-primary" style="border-radius:6px;padding:3px 9px;font-size:.75rem;">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if($u->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $u) }}"
                                  onsubmit="return confirm('Hapus user \'{{ addslashes($u->name) }}\'?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" style="border-radius:6px;padding:3px 9px;font-size:.75rem;">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada user</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</main>
</div>
@endsection
