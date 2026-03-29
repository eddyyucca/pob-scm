@extends('layouts.app')
@section('title','Notifikasi & Laporan Kehadiran')

@push('styles')
<style>
.kard{background:#fff;border-radius:12px;box-shadow:0 1px 8px rgba(0,0,0,.07);}
.kard-hdr{padding:13px 16px 10px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;}
.kard-title{font-size:.82rem;font-weight:700;color:#374151;}
.badge-ok{background:#dcfce7;color:#16a34a;border-radius:20px;font-size:.72rem;padding:3px 10px;font-weight:600;white-space:nowrap;}
.badge-no{background:#fee2e2;color:#dc2626;border-radius:20px;font-size:.72rem;padding:3px 10px;font-weight:600;white-space:nowrap;}
.tab-btn{border:none;background:none;padding:9px 18px;font-size:.83rem;color:#94a3b8;border-bottom:2px solid transparent;cursor:pointer;}
.tab-btn.on{color:#2563eb;border-bottom-color:#2563eb;font-weight:600;}
.tab-pane{display:none;}.tab-pane.on{display:block;}
.btn-notif{border:none;border-radius:20px;padding:4px 12px;font-size:.75rem;font-weight:600;cursor:pointer;white-space:nowrap;}
</style>
@endpush

@section('sidebar-nav')
@include('partials.sidebar')
@endsection

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0">Notifikasi & Laporan Kehadiran</h5>
            <small class="text-muted">Pantau laporan mingguan kontraktor dan kirim reminder WhatsApp</small>
        </div>
    </div>

    {{-- Alert hasil --}}
    @if(session('notif_result'))
    @php $nr = session('notif_result'); @endphp
    <div class="alert border-0 mb-3 py-2 px-3 d-flex align-items-center gap-2"
         style="border-radius:10px;background:{{ $nr['success']?'#f0fdf4':'#fef2f2' }};">
        <i class="bi {{ $nr['success']?'bi-check-circle-fill text-success':'bi-x-circle-fill text-danger' }}"></i>
        <span style="font-size:.85rem;">{{ $nr['message'] }}</span>
    </div>
    @endif
    @if(session('success'))
    <div class="alert alert-success border-0 mb-3 py-2 px-3" style="border-radius:10px;font-size:.85rem;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    </div>
    @endif

    {{-- Filter --}}
    <div class="kard mb-3 px-3 py-2">
        <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
            <i class="bi bi-calendar-range text-muted"></i>
            <label class="small fw-semibold text-muted mb-0">Dari:</label>
            <input type="date" name="from" class="form-control form-control-sm" style="width:auto;" value="{{ $from }}" max="{{ now()->toDateString() }}">
            <label class="small fw-semibold text-muted mb-0">Sampai:</label>
            <input type="date" name="to"   class="form-control form-control-sm" style="width:auto;" value="{{ $to }}"   max="{{ now()->toDateString() }}">
            <button class="btn btn-sm btn-primary" style="border-radius:8px;">Tampilkan</button>
            <a href="{{ route('notifications.index') }}?from={{ now()->startOfWeek()->toDateString() }}&to={{ now()->toDateString() }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">Minggu Ini</a>
        </form>
    </div>

    {{-- Stat cards --}}
    @php
        $total    = $notReported->count() + $reportedCompanies->count();
        $pct      = $total > 0 ? round($reportedCompanies->count()/$total*100) : 0;
    @endphp
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><div class="kard text-center p-3" style="border-top:3px solid #16a34a;">
            <div style="font-size:2rem;font-weight:700;color:#16a34a;">{{ $reportedCompanies->count() }}</div>
            <div style="font-size:.72rem;color:#64748b;">Sudah Lapor</div>
        </div></div>
        <div class="col-6 col-md-3"><div class="kard text-center p-3" style="border-top:3px solid #dc2626;">
            <div style="font-size:2rem;font-weight:700;color:#dc2626;">{{ $notReported->count() }}</div>
            <div style="font-size:.72rem;color:#64748b;">Belum Lapor</div>
        </div></div>
        <div class="col-6 col-md-3"><div class="kard text-center p-3" style="border-top:3px solid #2563eb;">
            <div style="font-size:2rem;font-weight:700;color:#2563eb;">{{ $total }}</div>
            <div style="font-size:.72rem;color:#64748b;">Total Perusahaan</div>
        </div></div>
        <div class="col-6 col-md-3"><div class="kard text-center p-3" style="border-top:3px solid #ea580c;">
            <div style="font-size:2rem;font-weight:700;color:#ea580c;">{{ $pct }}%</div>
            <div style="font-size:.72rem;color:#64748b;">Tingkat Kepatuhan</div>
            <div style="height:4px;background:#e2e8f0;border-radius:2px;margin-top:6px;">
                <div style="height:4px;width:{{ $pct }}%;background:#ea580c;border-radius:2px;"></div>
            </div>
        </div></div>
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

            {{-- Kirim semua otomatis --}}
            <div class="px-3 py-2 d-flex align-items-center gap-2 flex-wrap"
                 style="background:#fef2f2;border-bottom:1px solid #fecaca;">
                <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                <span style="font-size:.82rem;color:#dc2626;font-weight:600;">
                    {{ $notReported->count() }} perusahaan belum lapor periode
                    {{ \Carbon\Carbon::parse($from)->format('d M') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
                </span>
                <div class="ms-auto d-flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('notifications.send-scheduled') }}">
                        @csrf <input type="hidden" name="day" value="friday">
                        <button class="btn-notif" style="background:#fef9c3;color:#d97706;" onclick="return confirm('Kirim reminder ke semua yang belum lapor?')">
                            🔔 Kirim Reminder Semua
                        </button>
                    </form>
                    <form method="POST" action="{{ route('notifications.send-scheduled') }}">
                        @csrf <input type="hidden" name="day" value="saturday">
                        <button class="btn-notif" style="background:#fff7ed;color:#ea580c;" onclick="return confirm('Kirim peringatan Sabtu ke semua?')">
                            ⚠️ Peringatan Sabtu
                        </button>
                    </form>
                    <form method="POST" action="{{ route('notifications.send-scheduled') }}">
                        @csrf <input type="hidden" name="day" value="sunday">
                        <button class="btn-notif" style="background:#fee2e2;color:#dc2626;" onclick="return confirm('Kirim pemanggilan Minggu ke semua?')">
                            🚨 Pemanggilan Minggu
                        </button>
                    </form>
                </div>
            </div>

            {{-- Kirim per checkbox --}}
            <form method="POST" action="{{ route('notifications.send') }}" id="formBelum">
                @csrf
                <input type="hidden" name="day" value="friday">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle" style="font-size:.82rem;">
                        <thead style="background:#f8fafc;font-size:.72rem;color:#94a3b8;text-transform:uppercase;">
                            <tr>
                                <th class="px-3 py-2">
                                    <input type="checkbox" id="chkAll" onchange="toggleAll(this)">
                                </th>
                                <th>Perusahaan</th>
                                <th class="text-center">Kontak WA</th>
                                <th class="text-center">Status</th>
                                <th class="text-center pe-3">Kirim Per Perusahaan</th>
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
                            <td class="text-center pe-3">
                                @if($comp->contact_count > 0)
                                <div class="d-flex gap-1 justify-content-center">
                                    {{-- Kirim 1 perusahaan: Jumat --}}
                                    <form method="POST" action="{{ route('notifications.send-one') }}" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="company_id" value="{{ $comp->id }}">
                                        <input type="hidden" name="day" value="friday">
                                        <button class="btn-notif" style="background:#fef9c3;color:#d97706;" title="Kirim Reminder">🔔</button>
                                    </form>
                                    {{-- Sabtu --}}
                                    <form method="POST" action="{{ route('notifications.send-one') }}" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="company_id" value="{{ $comp->id }}">
                                        <input type="hidden" name="day" value="saturday">
                                        <button class="btn-notif" style="background:#fff7ed;color:#ea580c;" title="Peringatan Sabtu">⚠️</button>
                                    </form>
                                    {{-- Minggu --}}
                                    <form method="POST" action="{{ route('notifications.send-one') }}" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="company_id" value="{{ $comp->id }}">
                                        <input type="hidden" name="day" value="sunday">
                                        <button class="btn-notif" style="background:#fee2e2;color:#dc2626;" title="Pemanggilan">🚨</button>
                                    </form>
                                </div>
                                @else
                                <span style="color:#cbd5e1;font-size:.75rem;">Tidak ada kontak</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-3 py-2 border-top d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-sm btn-warning" style="border-radius:20px;font-size:.78rem;">
                        🔔 Kirim Reminder ke yang Dipilih
                    </button>
                    <span style="font-size:.75rem;color:#94a3b8;">centang perusahaan lalu klik tombol</span>
                </div>
            </form>

            @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-check-circle-fill" style="font-size:2.5rem;color:#16a34a;display:block;margin-bottom:8px;"></i>
                Semua perusahaan sudah lapor untuk periode ini 🎉
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
                        <td class="fw-semibold">{{ $comp->name ?? '-' }}</td>
                        <td class="text-center">{{ $comp->days_reported ?? 0 }} hari</td>
                        <td class="text-end fw-bold" style="color:#2563eb;">{{ number_format((int)($comp->total_pob ?? 0)) }}</td>
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
            <div class="text-center py-5 text-muted" style="font-size:.82rem;">Belum ada laporan untuk periode ini</div>
            @endif
        </div>

        {{-- TAB: LOG --}}
        <div id="tab-log" class="tab-pane p-0">
            @if($logs->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle" style="font-size:.8rem;">
                    <thead style="background:#f8fafc;font-size:.72rem;color:#94a3b8;text-transform:uppercase;">
                        <tr>
                            <th class="px-3 py-2">Waktu</th>
                            <th>Perusahaan</th>
                            <th>Penerima</th>
                            <th>Nomor WA</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td class="px-3 text-muted" style="white-space:nowrap;font-size:.78rem;">
                            {{ ($log->sent_at ?? $log->created_at)->format('d M Y H:i') }}
                        </td>
                        <td class="fw-semibold">{{ \Illuminate\Support\Str::limit($log->company->name??'-',28) }}</td>
                        <td class="text-muted">{{ $log->recipient_name ?? '-' }}</td>
                        <td class="font-monospace text-muted" style="font-size:.75rem;">{{ $log->phone }}</td>
                        <td class="text-center">
                            @if($log->status === 'sent')
                            <span class="badge-ok">Terkirim</span>
                            @elseif($log->status === 'failed')
                            <span class="badge-no">Gagal</span>
                            @else
                            <span style="background:#f1f5f9;color:#64748b;border-radius:20px;font-size:.72rem;padding:3px 10px;">Pending</span>
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
                <div class="col-lg-8">
                    @if($contacts->count() > 0)
                    <div style="max-height:420px;overflow-y:auto;">
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
                                        <button class="border-0 p-0" style="background:none;cursor:pointer;">
                                            @if($c->is_active)
                                            <span class="badge-ok" style="cursor:pointer;">Aktif</span>
                                            @else
                                            <span class="badge-no" style="cursor:pointer;">Nonaktif</span>
                                            @endif
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end pe-2">
                                    <form method="POST" action="{{ route('notifications.contact.destroy', $c) }}"
                                          onsubmit="return confirm('Hapus kontak ini?')" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button class="border-0" style="background:none;color:#ef4444;padding:2px 4px;cursor:pointer;">
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
    </div>{{-- end kard tabs --}}

    {{-- Keterangan sistem otomatis --}}
    <div class="mt-3 px-3 py-2 d-flex align-items-start gap-2"
         style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;font-size:.78rem;color:#0369a1;">
        <i class="bi bi-robot mt-1" style="flex-shrink:0;"></i>
        <div>
            <strong>Notifikasi Otomatis:</strong>
            🔔 <strong>Jumat</strong> — reminder pertama ke yang belum lapor sama sekali minggu ini &nbsp;|&nbsp;
            ⚠️ <strong>Sabtu</strong> — peringatan hari terakhir &nbsp;|&nbsp;
            🚨 <strong>Minggu</strong> — pemanggilan bagi yang masih belum lapor.<br>
            Notifikasi hanya dikirim ke perusahaan yang <strong>belum ada laporan sama sekali</strong> dalam minggu berjalan.
            Semua pesan dikirim otomatis oleh sistem dan mencantumkan keterangan tersebut.
        </div>
    </div>


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
