<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Berhasil – PT SCM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background:#e8f0f7; font-family:'Segoe UI',sans-serif; }
        .wrap { max-width:580px; margin:0 auto; padding:48px 16px; }
        .card-done { background:#fff; border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,.09); overflow:hidden; }
        .done-header { background:linear-gradient(135deg,#16a34a,#15803d); padding:36px 32px; text-align:center; color:#fff; }
        .done-body { padding:28px 32px; }
        .row-stat { display:flex; justify-content:space-between; align-items:center; padding:11px 0; border-bottom:1px solid #f1f5f9; font-size:.88rem; }
        .row-stat:last-child { border-bottom:none; }
        .lbl { color:#64748b; }
        .val { font-weight:700; color:#1e293b; }

        /* Sync summary box */
        .sync-box { background:#f8fafc; border-radius:10px; padding:16px 20px; margin:16px 0; }
        .sync-item { display:flex; align-items:center; gap:10px; padding:6px 0; font-size:.85rem; }
        .sync-icon { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:.8rem; }
    </style>
</head>
<body>
<div class="wrap">
@php $r = $result; @endphp

<div class="card-done">
    <div class="done-header">
        <div style="font-size:3.2rem;margin-bottom:10px;">&#x2705;</div>
        <h2 style="font-size:1.25rem;font-weight:700;margin-bottom:6px;">Laporan Berhasil Dikirim!</h2>
        <p style="font-size:.83rem;opacity:.85;margin:0;">
            Data POB dan daftar karyawan <strong>{{ \Carbon\Carbon::parse($r['date'])->format('d M Y') }}</strong>
            telah tersimpan
        </p>
    </div>

    <div class="done-body">

        {{-- Info laporan --}}
        <div class="row-stat">
            <span class="lbl">Perusahaan</span>
            <span class="val">{{ $r['company'] }}</span>
        </div>
        <div class="row-stat">
            <span class="lbl">Tanggal</span>
            <span class="val">{{ \Carbon\Carbon::parse($r['date'])->format('d M Y') }}</span>
        </div>
        <div class="row-stat">
            <span class="lbl">Total POB Final</span>
            <span class="val" style="color:#2563eb;font-size:1.1rem;">{{ $r['total_pob'] }} orang</span>
        </div>
        <div class="row-stat">
            <span class="lbl">Total Manpower</span>
            <span class="val" style="color:#16a34a;">{{ $r['total_mp'] }} orang</span>
        </div>
        <div class="row-stat">
            <span class="lbl">Rasio POB / Manpower</span>
            <span class="val" style="color:#ea580c;">
                @php
                    $ratio = $r['total_mp'] > 0 ? round($r['total_pob'] / $r['total_mp'] * 100, 1) : 0;
                @endphp
                {{ $ratio }}%
            </span>
        </div>

        {{-- Sinkronisasi karyawan --}}
        <div style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin:18px 0 6px;">
            Sinkronisasi Data Karyawan
        </div>
        <div class="sync-box">

            {{-- Apakah ini upload baru atau koreksi? --}}
            @if($r['updated'] > 0 && $r['new'] === 0 && $r['removed'] === 0)
            {{-- Upload ulang / koreksi hari yang sama --}}
            <div class="sync-item">
                <div class="sync-icon" style="background:#eff6ff;color:#2563eb;">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <div>
                    <div style="font-weight:600;color:#1e293b;">Data hari ini diperbarui</div>
                    <div style="font-size:.78rem;color:#64748b;">
                        {{ $r['total_pob'] }} karyawan — data lama diganti dengan file terbaru
                    </div>
                </div>
            </div>
            @else
            @if($r['new'] > 0)
            <div class="sync-item">
                <div class="sync-icon" style="background:#f0fdf4;color:#16a34a;">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <div>
                    <div style="font-weight:600;color:#16a34a;">+{{ $r['new'] }} karyawan baru ditambahkan</div>
                    <div style="font-size:.78rem;color:#64748b;">Belum ada di data sebelumnya</div>
                </div>
            </div>
            @endif
            @if($r['updated'] > 0)
            <div class="sync-item">
                <div class="sync-icon" style="background:#eff6ff;color:#2563eb;">
                    <i class="bi bi-person-check-fill"></i>
                </div>
                <div>
                    <div style="font-weight:600;color:#2563eb;">{{ $r['updated'] }} data karyawan tetap</div>
                    <div style="font-size:.78rem;color:#64748b;">Masih onsite dari sebelumnya</div>
                </div>
            </div>
            @endif
            @if($r['removed'] > 0)
            <div class="sync-item">
                <div class="sync-icon" style="background:#fff7ed;color:#ea580c;">
                    <i class="bi bi-person-dash-fill"></i>
                </div>
                <div>
                    <div style="font-weight:600;color:#ea580c;">{{ $r['removed'] }} karyawan tidak lagi onsite</div>
                    <div style="font-size:.78rem;color:#64748b;">Dihapus dari daftar hari ini</div>
                </div>
            </div>
            @endif
            @if($r['new'] === 0 && $r['updated'] === 0 && $r['removed'] === 0)
            <div class="sync-item">
                <div class="sync-icon" style="background:#f0fdf4;color:#16a34a;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div style="font-weight:600;color:#16a34a;">{{ $r['total_pob'] }} karyawan tersimpan</div>
                    <div style="font-size:.78rem;color:#64748b;">Data berhasil disinkronisasi</div>
                </div>
            </div>
            @endif
            @endif
        </div>

        {{-- Warning: jumlah tidak cocok --}}
        @if($r['mismatch'])
        <div class="alert alert-warning border-0 py-2 px-3 mb-3" style="border-radius:8px;font-size:.82rem;">
            <i class="bi bi-info-circle-fill me-1"></i>
            Jumlah karyawan di file <strong>({{ $r['total_pob'] }})</strong> berbeda dengan laporan awal
            <strong>({{ $r['original_pob'] }})</strong>. Total POB diperbarui otomatis ke
            <strong>{{ $r['total_pob'] }}</strong>.
        </div>
        @endif

        {{-- Baris error --}}
        @if(count($r['row_errors']) > 0)
        <div class="alert border-0 py-2 px-3 mb-3" style="background:#fef9c3;border-radius:8px;font-size:.82rem;">
            <div style="font-weight:600;color:#92400e;margin-bottom:4px;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                {{ count($r['row_errors']) }} baris diabaikan karena data tidak valid
            </div>
            <details>
                <summary style="cursor:pointer;color:#92400e;font-size:.78rem;">Lihat detail</summary>
                <div style="margin-top:6px;">
                    @foreach(array_slice($r['row_errors'], 0, 10) as $e)
                    <div style="color:#78350f;">• {{ $e }}</div>
                    @endforeach
                    @if(count($r['row_errors']) > 10)
                    <div style="color:#78350f;">... dan {{ count($r['row_errors']) - 10 }} lainnya</div>
                    @endif
                </div>
            </details>
        </div>
        @endif

        {{-- Catatan histori --}}
        <div style="background:#f0fdf4;border-radius:8px;padding:10px 14px;font-size:.78rem;color:#166534;margin-bottom:20px;">
            <i class="bi bi-calendar-check me-1"></i>
            Data karyawan hari ini tersimpan sebagai histori.
            Admin dapat melihat siapa yang onsite di tanggal tertentu kapan saja.
        </div>

        <a href="{{ route('form.index') }}" class="btn btn-primary w-100 fw-semibold py-2" style="border-radius:8px;">
            <i class="bi bi-plus-circle me-2"></i>Kirim Laporan Baru
        </a>

        <div class="text-center mt-3" style="font-size:.72rem;color:#94a3b8;">
            PT Sulawesi Cahaya Mineral — Sistem POB Harian
        </div>
    </div>
</div>
</div>
</body>
</html>
