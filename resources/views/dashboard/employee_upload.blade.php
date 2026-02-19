@extends('layouts.app')
@section('title','Upload Data Karyawan POB')
@section('content')
<div class="d-flex" style="min-height:100vh;">

{{-- Sidebar --}}
<nav class="d-flex flex-column p-3" style="width:220px;min-height:100vh;background:#1a3c5e;color:#fff;flex-shrink:0;">
    <div class="mb-4 mt-2 text-center">
        <div style="font-size:1.05rem;font-weight:700;">&#x26CF; SCM Nickel</div>
        <div style="font-size:.72rem;opacity:.55;">POB & Manpower System</div>
    </div>
    <a href="{{ route('dashboard') }}" class="nav-link text-white py-2 px-3 rounded mb-1" style="opacity:.7;"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
    <a href="{{ route('employees.index') }}" class="nav-link text-white py-2 px-3 rounded mb-1" style="opacity:.7;"><i class="bi bi-people me-2"></i>Data Karyawan</a>
    <a href="{{ route('employees.upload') }}" class="nav-link text-white py-2 px-3 rounded mb-1" style="background:rgba(255,255,255,.15);"><i class="bi bi-person-plus me-2"></i>Upload Karyawan</a>
    <a href="{{ route('dashboard.import') }}" class="nav-link text-white py-2 px-3 rounded mb-1" style="opacity:.7;"><i class="bi bi-upload me-2"></i>Import POB</a>
    <div class="mt-auto pt-3 border-top border-secondary">
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button class="btn btn-sm btn-outline-light w-100"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
        </form>
    </div>
</nav>

<main class="flex-grow-1 p-4" style="background:#f0f4f8;">
    <div class="mb-4">
        <h4 class="fw-bold mb-0">Upload Data Karyawan POB</h4>
        <small class="text-muted">Upload daftar karyawan yang onsite sesuai laporan POB harian</small>
    </div>

    {{-- Upload result --}}
    @if(session('upload_result'))
    @php $r = session('upload_result'); @endphp
    <div class="alert border-0 shadow-sm mb-4" style="border-radius:12px;background:#d1fae5;">
        <h6 class="fw-bold mb-2 text-success"><i class="bi bi-check-circle-fill me-2"></i>Upload Berhasil</h6>
        <div class="row text-center g-2">
            <div class="col-3"><div class="bg-white rounded p-2"><div style="font-size:1.5rem;font-weight:700;color:#059669;">{{ $r['inserted'] }}</div><div style="font-size:.72rem;">Karyawan Tersimpan</div></div></div>
            <div class="col-3"><div class="bg-white rounded p-2"><div style="font-size:1.5rem;font-weight:700;color:#0d6efd;">{{ $r['pob_count'] }}</div><div style="font-size:.72rem;">Total POB Laporan</div></div></div>
            <div class="col-3"><div class="bg-white rounded p-2"><div style="font-size:.9rem;font-weight:700;color:#374151;" class="py-1">{{ $r['company'] }}</div><div style="font-size:.72rem;">Perusahaan</div></div></div>
            <div class="col-3"><div class="bg-white rounded p-2"><div style="font-size:.9rem;font-weight:700;color:#374151;" class="py-1">{{ \Carbon\Carbon::parse($r['date'])->format('d M Y') }}</div><div style="font-size:.72rem;">Tanggal</div></div></div>
        </div>
        @if($r['inserted'] != $r['pob_count'])
        <div class="alert alert-warning mt-2 mb-0 py-2 px-3 small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Jumlah karyawan ({{ $r['inserted'] }}) berbeda dengan Total POB laporan ({{ $r['pob_count'] }}). Total POB telah diperbarui otomatis.
        </div>
        @endif
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger border-0 mb-4" style="border-radius:12px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
    </div>
    @endif

    <div class="row g-4">
        {{-- Form Upload --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm" style="border-radius:14px;">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark-person text-primary me-2"></i>Upload Daftar Karyawan</h6>

                    <form action="{{ route('employees.upload.post') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Perusahaan <span class="text-danger">*</span></label>
                            <select name="company_id" class="form-select" required id="sel-company">
                                <option value="">-- Pilih Perusahaan --</option>
                                @foreach($companies as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Tanggal Laporan <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required id="sel-date">
                        </div>

                        {{-- Info POB entry --}}
                        <div id="pob-info" class="alert alert-info py-2 px-3 small mb-3 d-none">
                            <i class="bi bi-info-circle me-1"></i>
                            <span id="pob-info-text"></span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">File Excel Karyawan <span class="text-danger">*</span></label>
                            <div id="dropZone" class="border border-2 border-dashed rounded-3 text-center p-4"
                                 style="border-color:#cbd5e1;cursor:pointer;background:#f8fafc;"
                                 onclick="document.getElementById('excel_file').click()">
                                <i class="bi bi-person-lines-fill" style="font-size:2rem;color:#94a3b8;"></i>
                                <div class="mt-1 fw-semibold text-muted small">Klik atau drag & drop file Excel</div>
                                <div class="text-muted" style="font-size:.75rem;">.xlsx / .xls — Maks. 20MB</div>
                                <div id="fileName" class="mt-1 text-primary fw-semibold d-none small"></div>
                            </div>
                            <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" class="d-none">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-upload me-2"></i>Upload & Simpan Data Karyawan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Panduan & Template --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-3" style="border-radius:14px;">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-table text-success me-2"></i>Format Kolom Excel</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" style="font-size:.8rem;">
                            <thead class="table-primary">
                                <tr>
                                    <th>Kolom</th>
                                    <th>Keterangan</th>
                                    <th>Wajib</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td><code>ID / MinePermit / KTP</code></td><td>Nomor identitas</td><td class="text-center"><i class="bi bi-dash text-muted"></i></td></tr>
                                <tr><td><code>Nama / Name</code></td><td>Nama lengkap</td><td class="text-center text-danger fw-bold">*</td></tr>
                                <tr><td><code>Jabatan / Position</code></td><td>Jabatan/posisi</td><td class="text-center"><i class="bi bi-dash text-muted"></i></td></tr>
                                <tr><td><code>Departemen / Department</code></td><td>Departemen</td><td class="text-center"><i class="bi bi-dash text-muted"></i></td></tr>
                                <tr><td><code>Tipe / Type</code></td><td>employee / visitor</td><td class="text-center"><i class="bi bi-dash text-muted"></i></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('employees.template') }}" class="btn btn-sm btn-outline-success w-100">
                            <i class="bi bi-download me-1"></i>Download Template Excel
                        </a>
                    </div>
                </div>
            </div>

            {{-- POB laporan yang belum punya data karyawan --}}
            @if($pendingEntries->count() > 0)
            <div class="card border-0 shadow-sm" style="border-radius:14px;">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-2 small"><i class="bi bi-exclamation-circle text-warning me-1"></i>POB Belum Ada Data Karyawan</h6>
                    <div style="max-height:200px;overflow-y:auto;">
                        @foreach($pendingEntries as $entry)
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="font-size:.78rem;">
                            <div>
                                <span class="fw-semibold">{{ Str::limit($entry->company->name, 25) }}</span><br>
                                <span class="text-muted">{{ \Carbon\Carbon::parse($entry->date)->format('d M Y') }} — POB: {{ $entry->total_pob }}</span>
                            </div>
                            <button class="btn btn-xs btn-outline-primary" style="font-size:.7rem;padding:2px 8px;"
                                onclick="fillForm({{ $entry->company_id }}, '{{ $entry->date->format('Y-m-d') }}')">
                                Pilih
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</main>
</div>

<script>
const fileInput = document.getElementById('excel_file');
const fileName  = document.getElementById('fileName');
const dropZone  = document.getElementById('dropZone');

fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) {
        fileName.textContent = '📎 ' + fileInput.files[0].name;
        fileName.classList.remove('d-none');
        dropZone.style.borderColor = '#0d6efd';
        dropZone.style.background  = '#eff6ff';
    }
});

dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = '#0d6efd'; });
dropZone.addEventListener('dragleave', () => dropZone.style.borderColor = '#cbd5e1');
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer(); dt.items.add(file); fileInput.files = dt.files;
        fileName.textContent = '📎 ' + file.name;
        fileName.classList.remove('d-none');
    }
});

function fillForm(companyId, date) {
    document.getElementById('sel-company').value = companyId;
    document.getElementById('sel-date').value = date;
    document.getElementById('pob-info').classList.remove('d-none');
    document.getElementById('pob-info-text').textContent = 'POB untuk tanggal ' + date + ' dipilih. Upload file karyawan sesuai tanggal ini.';
    window.scrollTo({top: 0, behavior: 'smooth'});
}
</script>
@endsection
