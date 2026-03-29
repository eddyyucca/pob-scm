@extends('layouts.app')
@section('title','Notifikasi & Laporan Kehadiran')

@push('styles')
<style>
:root{--blue:#2563eb;--green:#16a34a;--orange:#ea580c;--red:#dc2626;--purple:#7c3aed;}
.sb{width:220px;min-height:100vh;background:#1a3c5e;flex-shrink:0;display:flex;flex-direction:column;padding:14px 10px;}
.sb-logo{text-align:center;padding:8px 0 20px;color:#fff;}
.sb-logo h6{font-size:1rem;font-weight:700;margin:0;}
.sb-logo small{font-size:.68rem;opacity:.4;}
.sb a{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:8px;color:rgba(255,255,255,.7);text-decoration:none;font-size:.84rem;margin-bottom:2px;transition:all .15s;}
.sb a:hover,.sb a.on{background:rgba(255,255,255,.15);color:#fff;}
.kard{background:#fff;border-radius:12px;box-shadow:0 1px 8px rgba(0,0,0,.07);border:none;}
.kard-hdr{padding:13px 16px 10px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;}
.kard-title{font-size:.82rem;font-weight:700;color:#374151;}
.badge-ok{background:#dcfce7;color:#16a34a;border-radius:20px;font-size:.72rem;padding:3px 10px;font-weight:600;white-space:nowrap;}
.badge-no{background:#fee2e2;color:#dc2626;border-radius:20px;font-size:.72rem;padding:3px 10px;font-weight:600;white-space:nowrap;}
.tab-btn{border:none;background:none;padding:8px 18px;font-size:.83rem;color:#94a3b8;border-bottom:2px solid transparent;cursor:pointer;transition:all .15s;}
.tab-btn.on{color:#2563eb;border-bottom-color:#2563eb;font-weight:600;}
.tab-pane{display:none;}.tab-pane.on{display:block;}
</style>
@endpush

@section('content')
<div class="d-flex">

{{-- SIDEBAR --}}
@include('partials.sidebar')

{{-- MAIN --}}
<main style="flex:1;padding:22px;overflow-x:hidden;">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0" style="color:#1e293b;">Notifikasi & Laporan Kehadiran</h5>
            <small class="text-muted">Pantau perusahaan yang sudah dan belum melapor, kirim reminder WhatsApp</small>
        </div>
    </div>

    {{-- Alert hasil kirim --}}
    @if(session('notif_result'))
    @php $nr = session('notif_result'); @endphp
    <div class="alert border-0 mb-3 py-2 px-3" style="border-radius:10px;background:{{ $nr['success']?'#f0fdf4':'#fef2f2' }};">
        <i class="bi {{ $nr['success']?'bi-check-circle-fill text-success':'bi-x-circle-fill text-danger' }} me-2"></i>
        {{ $nr['message'] }}
    </div>
    @endif
    @if(session('success'))
    <div class="alert alert-success border-0 mb-3 py-2 px-3" style="border-radius:10px;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    </div>
    @endif

    {{-- Filter Rentang Tanggal --}}
    <div class="kard mb-3 px-3 py-2">
        <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
            <i class="bi bi-calendar-range text-muted"></i>
            <label class="small fw-semibold text-muted mb-0">Dari:</label>
            <input type="date" name="from" class="form-control form-control-sm" style="width:auto;" value="{{ $from }}" max="{{ now()->toDateString() }}">
            <label class="small fw-semibold text-muted mb-0">Sampai:</label>
            <input type="date" name="to" class="form-control form-control-sm" style="width:auto;" value="{{ $to }}" max="{{ now()->toDateString() }}">
            <button class="btn btn-sm btn-primary" style="border-radius:8px;">Tampilkan</button>
            <a href="{{ route('notifications.index') }}?from={{ now()->startOfWeek()->toDateString() }}&to={{ now()->toDateString() }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">Minggu Ini</a>
            <a href="{{ route('notifications.index') }}?from={{ now()->startOfMonth()->toDateString() }}&to={{ now()->toDateString() }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">Bulan Ini</a>
        </form>
    </div>

    {{-- STAT CARDS --}}
    <div class="row g-3 mb-3">
        @php
        $totalComp   = $notReported->count() + $reportedCompanies->count();
        $pctReported = $totalComp > 0 ? round($reportedCompanies->count()/$totalComp*100) : 0;
        @endphp
        <div class="col-6 col-md-3">
            <div class="kard text-center p-3" style="border-top:3px solid var(--green);">
                <div style="font-size:2rem;font-weight:700;color:var(--green);">{{ $reportedCompanies->count() }}</div>
                <div style="font-size:.72rem;color:#64748b;">Sudah Lapor</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kard text-center p-3" style="border-top:3px solid var(--red);">
                <div style="font-size:2rem;font-weight:700;color:var(--red);">{{ $notReported->count() }}</div>
                <div style="font-size:.72rem;color:#64748b;">Belum Lapor</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kard text-center p-3" style="border-top:3px solid var(--blue);">
                <div style="font-size:2rem;font-weight:700;color:var(--blue);">{{ $totalComp }}</div>
                <div style="font-size:.72rem;color:#64748b;">Total Perusahaan</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kard text-center p-3" style="border-top:3px solid var(--orange);">
                <div style="font-size:2rem;font-weight:700;color:var(--orange);">{{ $pctReported }}%</div>
                <div style="font-size:.72rem;color:#64748b;">Tingkat Kepatuhan</div>
                <div style="height:4px;background:#e2e8f0;border-radius:2px;margin-top:6px;">
                    <div style="height:4px;width:{{ $pctReported }}%;background:var(--orange);border-radius:2px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- TABS --}}
    <div class="kard mb-3">
        <div style="border-bottom:1px solid #f1f5f9;padding:0 16px;display:flex;gap:0;">
            <button class="tab-btn on" onclick="showTab('tab-belum',this)">
                <i class="bi bi-x-circle me-1"></i>Belum Lapor
                <span style="background:#fee2e2;color:#dc2626;border-radius:20px;font-size:.68rem;padding:1px 7px;margin-left:4px;">{{ $notReported->count() }}</span>
            </button>
            <button class="tab-btn" onclick="showTab('tab-sudah',this)">
                <i class="bi bi-check-circle me-1"></i>Sudah Lapor
                <span style="background:#dcfce7;color:#16a34a;border-radius:20px;font-size:.68rem;padding:1px 7px;margin-left:4px;">{{ $reportedCompanies->count() }}</span>
            </button>
            <button class="tab-btn" onclick="showTab('tab-log',this)">
                <i class="bi bi-clock-history me-1"></i>Log Notifikasi
            </button>
            <button class="tab-btn" onclick="showTab('tab-kontak',this)">
                <i class="bi bi-person-lines-fill me-1"></i>Kontak WA
            </button>
        </div>

        {{-- TAB: BELUM LAPOR --}}
        <div id="tab-belum" class="tab-pane on p-0">
            @if($notReported->count() > 0)
            <div class="px-3 py-2 d-flex justify-content-between align-items-center" style="background:#fef2f2;border-bottom:1px solid #fecaca;">
                <span style="font-size:.82rem;color:#dc2626;font-weight:600;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    {{ $notReported->count() }} perusahaan belum lapor periode {{ \Carbon\Carbon::parse($from)->format('d M') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
                </span>
                <div class="d-flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('notifications.send-scheduled') }}">
                        @csrf <input type="hidden" name="day" value="friday">
                        <button class="btn btn-sm btn-warning" style="border-radius:20px;font-size:.75rem;"
                            onclick="return confirm('Kirim reminder Jumat ke semua yang belum lapor?')">
                            <i class="bi bi-bell me-1"></i>🔔 Reminder (Jumat)
                        </button>
                    </form>
                    <form method="POST" action="{{ route('notifications.send-scheduled') }}">
                        @csrf <input type="hidden" name="day" value="saturday">
                        <button class="btn btn-sm btn-orange" style="border-radius:20px;font-size:.75rem;background:#ea580c;color:#fff;border:none;"
                            onclick="return confirm('Kirim peringatan Sabtu?')">
                            <i class="bi bi-exclamation-triangle me-1"></i>⚠️ Peringatan (Sabtu)
                        </button>
                    </form>
                    <form method="POST" action="{{ route('notifications.send-scheduled') }}">
                        @csrf <input type="hidden" name="day" value="sunday">
                        <button class="btn btn-sm btn-danger" style="border-radius:20px;font-size:.75rem;"
                            onclick="return confirm('Kirim pemanggilan Minggu?')">
                            <i class="bi bi-megaphone me-1"></i>🚨 Pemanggilan (Minggu)
                        </button>
                    </form>
                </div>
            </div>
            <form method="POST" action="{{ route('notifications.send') }}" id="formBelum">
                @csrf
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle" style="font-size:.82rem;">
                        <thead style="background:#f8fafc;font-size:.72rem;color:#94a3b8;text-transform:uppercase;">
                            <tr>
                                <th class="px-3 py-2">
                                    <input type="checkbox" id="chkAll" onchange="toggleAll(this)"> Semua
                                </th>
                                <th>Perusahaan</th>
                                <th class="text-center">Kontak WA</th>
                                <th class="text-center">Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($notReported as $comp)
                        <tr>
                            <td class="px-3">
                                <input type="checkbox" name="company_ids[]" value="{{ $comp->id }}" class="chk-item">
                            </td>
                            <td class="fw-semibold">{{ $comp->name }}</td>
                            <td class="text-center">
                                @if($comp->contact_count > 0)
                                <span class="badge-ok">{{ $comp->contact_count }} kontak</span>
                                @else
                                <span class="badge-no">Belum ada kontak</span>
                                @endif
                            </td>
                            <td class="text-center"><span class="badge-no">Belum Lapor</span></td>
                            <td class="text-end pe-3">
                                @if($comp->contact_count > 0)
                                <button type="submit" name="company_ids[]" value="{{ $comp->id }}"
                                    class="btn btn-sm btn-outline-danger" style="border-radius:20px;font-size:.72rem;"
                                    onclick="document.getElementById('formBelum').querySelectorAll('.chk-item').forEach(c=>c.checked=false);">
                                    <i class="bi bi-whatsapp me-1"></i>Kirim
                                </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-3 py-2 border-top">
                    <button type="submit" class="btn btn-sm btn-danger" style="border-radius:20px;font-size:.78rem;">
                        <i class="bi bi-whatsapp me-1"></i>Kirim Reminder ke yang Dipilih
                    </button>
                </div>
            </form>
            @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-check-circle-fill" style="font-size:2.5rem;color:#16a34a;display:block;margin-bottom:8px;"></i>
                Semua perusahaan sudah lapor untuk periode ini! 🎉
            </div>
            @endif
        </div>

        {{-- TAB: SUDAH LAPOR --}}
        <div id="tab-sudah" class="tab-pane p-0">
            @if($reportedCompanies->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle" style="font-size:.82rem;">
                    <thead style="background:#f8fafc;font-size:.72rem;color:#94a3b8;text-transform:uppercase;">
                        <tr>
                            <th class="px-3 py-2">#</th>
                            <th>Perusahaan</th>
                            <th class="text-center">Hari Lapor</th>
                            <th class="text-end">Total POB</th>
                            <th class="text-center">Laporan Terakhir</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($reportedCompanies as $i => $comp)
                    <tr>
                        <td class="px-3" style="color:#cbd5e1;font-size:.72rem;">{{ $i+1 }}</td>
                        <td class="fw-semibold">{{ $comp->name }}</td>
                        <td class="text-center">{{ $comp->days_reported ?? 0 }} hari</td>
                        <td class="text-end fw-bold" style="color:var(--blue);">{{ number_format($comp->total_pob ?? 0) }}</td>
                        <td class="text-center text-muted" style="font-size:.78rem;">
                            {{ $comp->last_report ? \Carbon\Carbon::parse($comp->last_report)->format('d M Y') : '-' }}
                        </td>
                        <td class="text-center"><span class="badge-ok">Sudah Lapor ✔</span></td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5 text-muted" style="font-size:.82rem;">
                Belum ada laporan untuk periode ini
            </div>
            @endif
        </div>

        {{-- TAB: LOG NOTIFIKASI --}}
        <div id="tab-log" class="tab-pane p-0">
            @if($logs->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle" style="font-size:.8rem;">
                    <thead style="background:#f8fafc;font-size:.72rem;color:#94a3b8;text-transform:uppercase;">
                        <tr>
                            <th class="px-3 py-2">Waktu Kirim</th>
                            <th>Perusahaan</th>
                            <th>Penerima</th>
                            <th>Nomor WA</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td class="px-3 text-muted" style="white-space:nowrap;">
                            {{ $log->sent_at ? $log->sent_at->format('d M Y H:i') : $log->created_at->format('d M Y H:i') }}
                        </td>
                        <td class="fw-semibold">{{ \Illuminate\Support\Str::limit($log->company->name??'-', 28) }}</td>
                        <td class="text-muted">{{ $log->recipient_name ?? '-' }}</td>
                        <td class="font-monospace text-muted" style="font-size:.75rem;">{{ $log->phone }}</td>
                        <td class="text-center">
                            @if($log->status === 'sent')
                            <span class="badge-ok">Terkirim</span>
                            @elseif($log->status === 'failed')
                            <span class="badge-no">Gagal</span>
                            @else
                            <span style="background:#f1f5f9;color:#64748b;border-radius:20px;font-size:.72rem;padding:3px 10px;font-weight:600;">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5 text-muted" style="font-size:.82rem;">
                <i class="bi bi-clock-history" style="font-size:2rem;display:block;opacity:.3;margin-bottom:8px;"></i>
                Belum ada riwayat pengiriman notifikasi
            </div>
            @endif
        </div>

        {{-- TAB: KONTAK WA --}}
        <div id="tab-kontak" class="tab-pane">
            <div class="row g-3 p-3">
                {{-- Form tambah kontak --}}
                <div class="col-lg-4">
                    <div style="background:#f8fafc;border-radius:10px;padding:16px;">
                        <h6 class="fw-bold mb-3" style="font-size:.85rem;"><i class="bi bi-person-plus me-1"></i>Tambah Kontak</h6>
                        <form method="POST" action="{{ route('notifications.contact.store') }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Perusahaan <span class="text-danger">*</span></label>
                                <select name="company_id" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach($companies as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Nama PIC <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm" placeholder="Nama lengkap" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Nomor WA <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control form-control-sm" placeholder="08xxxxxxxxxx" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Jabatan</label>
                                <input type="text" name="position" class="form-control form-control-sm" placeholder="Supervisor, PIC, dll">
                            </div>
                            <button class="btn btn-primary btn-sm w-100" style="border-radius:8px;">
                                <i class="bi bi-plus-circle me-1"></i>Simpan Kontak
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Daftar kontak --}}
                <div class="col-lg-8">
                    @if($contacts->count() > 0)
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                        <table class="table table-sm table-hover mb-0 align-middle" style="font-size:.8rem;">
                            <thead style="background:#f8fafc;position:sticky;top:0;font-size:.72rem;color:#94a3b8;text-transform:uppercase;">
                                <tr>
                                    <th class="py-2 px-2">Perusahaan</th>
                                    <th>Nama PIC</th>
                                    <th>Nomor WA</th>
                                    <th>Jabatan</th>
                                    <th class="text-center">Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($contacts->sortBy('company.name') as $c)
                            <tr>
                                <td class="px-2 fw-semibold" style="font-size:.78rem;">{{ \Illuminate\Support\Str::limit($c->company->name??'-',22) }}</td>
                                <td>{{ $c->name }}</td>
                                <td class="font-monospace" style="font-size:.75rem;">{{ $c->phone }}</td>
                                <td class="text-muted" style="font-size:.78rem;">{{ $c->position??'-' }}</td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('notifications.contact.toggle', $c) }}" style="display:inline;">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-xs p-0 border-0" style="background:none;" title="Klik untuk toggle">
                                            @if($c->is_active)
                                            <span class="badge-ok" style="cursor:pointer;">Aktif</span>
                                            @else
                                            <span class="badge-no" style="cursor:pointer;">Nonaktif</span>
                                            @endif
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end pe-2">
                                    <form method="POST" action="{{ route('notifications.contact.destroy', $c) }}" style="display:inline;"
                                          onsubmit="return confirm('Hapus kontak ini?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-xs" style="background:none;border:none;color:#ef4444;padding:2px 4px;" title="Hapus">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5 text-muted" style="font-size:.82rem;">
                        <i class="bi bi-person-x" style="font-size:2rem;display:block;opacity:.3;margin-bottom:8px;"></i>
                        Belum ada kontak. Tambahkan kontak WA perusahaan.
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>{{-- end kard --}}

</main>
</div>

<script>
function showTab(id, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('on'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('on'));
    document.getElementById(id).classList.add('on');
    btn.classList.add('on');
}
function toggleAll(master) {
    document.querySelectorAll('.chk-item').forEach(c => c.checked = master.checked);
}
</script>
@endsection