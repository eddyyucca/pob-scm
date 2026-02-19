@extends('layouts.app')

@section('title', 'Import Data Excel')

@section('content')
<div class="d-flex" style="min-height:100vh;">

    {{-- Sidebar --}}
    <nav class="sidebar d-flex flex-column p-3" style="width:240px;min-height:100vh;background:#1a3c5e;color:#fff;flex-shrink:0;">
        <div class="mb-4 mt-2 text-center">
            <div style="font-size:1.1rem;font-weight:700;letter-spacing:1px;">⛏ SCM Nickel</div>
            <div style="font-size:.75rem;opacity:.6;">POB & Manpower System</div>
        </div>
        <a href="{{ route('dashboard') }}" class="nav-link text-white py-2 px-3 rounded mb-1" style="opacity:.75;">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a>
        <a href="{{ route('dashboard.import') }}" class="nav-link text-white py-2 px-3 rounded mb-1 active" style="background:rgba(255,255,255,.15);">
            <i class="bi bi-upload me-2"></i>Import Excel
        </a>
        <a href="{{ route('dashboard.export') }}" class="nav-link text-white py-2 px-3 rounded mb-1" style="opacity:.75;">
            <i class="bi bi-download me-2"></i>Export CSV
        </a>
        <div class="mt-auto pt-3 border-top border-secondary">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-sm btn-outline-light w-100">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </button>
            </form>
        </div>
    </nav>

    {{-- Main --}}
    <main class="flex-grow-1 p-4" style="background:#f0f4f8;">
        <div class="mb-4">
            <h4 class="fw-bold mb-0">Import Data Excel</h4>
            <small class="text-muted">Upload file Excel berisi data POB & Manpower. Data yang sudah ada akan di-update.</small>
        </div>

        {{-- Hasil Import --}}
        @if(session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="alert border-0 shadow-sm mb-4" style="border-radius:12px;background:#d1fae5;border-left:4px solid #059669 !important;">
            <h6 class="fw-bold mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Import Selesai</h6>
            <div class="row text-center g-2 mb-2">
                <div class="col-3">
                    <div class="bg-white rounded p-2">
                        <div style="font-size:1.5rem;font-weight:700;color:#059669;">{{ $result['inserted'] }}</div>
                        <div style="font-size:.75rem;color:#6b7280;">Baris Baru</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="bg-white rounded p-2">
                        <div style="font-size:1.5rem;font-weight:700;color:#0d6efd;">{{ $result['updated'] }}</div>
                        <div style="font-size:.75rem;color:#6b7280;">Diperbarui/Skip</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="bg-white rounded p-2">
                        <div style="font-size:1.5rem;font-weight:700;color:#d97706;">{{ $result['new_companies'] }}</div>
                        <div style="font-size:.75rem;color:#6b7280;">Perusahaan Baru</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="bg-white rounded p-2">
                        <div style="font-size:1.5rem;font-weight:700;color:#dc3545;">{{ count($result['errors']) }}</div>
                        <div style="font-size:.75rem;color:#6b7280;">Error Baris</div>
                    </div>
                </div>
            </div>
            @if(count($result['errors']) > 0)
            <details class="mt-2">
                <summary class="text-danger small" style="cursor:pointer;">Lihat detail error ({{ count($result['errors']) }} baris)</summary>
                <ul class="mt-2 mb-0 small text-danger">
                    @foreach(array_slice($result['errors'], 0, 20) as $err)
                    <li>{{ $err }}</li>
                    @endforeach
                    @if(count($result['errors']) > 20)
                    <li>... dan {{ count($result['errors']) - 20 }} error lainnya</li>
                    @endif
                </ul>
            </details>
            @endif
        </div>
        @endif

        {{-- Error validasi --}}
        @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius:12px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ $errors->first() }}
        </div>
        @endif

        <div class="row g-4">
            {{-- Form Upload --}}
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm" style="border-radius:14px;">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark-excel text-success me-2"></i>Upload File Excel</h6>

                        <form action="{{ route('dashboard.import.upload') }}" method="POST" enctype="multipart/form-data" id="importForm">
                            @csrf

                            <div class="mb-4">
                                <div id="dropZone" class="border border-2 border-dashed rounded-3 text-center p-5"
                                     style="border-color:#cbd5e1!important;cursor:pointer;transition:all .2s;background:#f8fafc;"
                                     onclick="document.getElementById('excel_file').click()">
                                    <i class="bi bi-cloud-upload" style="font-size:2.5rem;color:#94a3b8;"></i>
                                    <div class="mt-2 fw-semibold text-muted">Klik atau drag & drop file di sini</div>
                                    <div class="text-muted small mt-1">Format: .xlsx, .xls — Maks. 20MB</div>
                                    <div id="fileName" class="mt-2 text-primary fw-semibold d-none"></div>
                                </div>
                                <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" class="d-none">
                            </div>

                            <div class="alert alert-info border-0 py-2 px-3 mb-4" style="border-radius:8px;font-size:.85rem;background:#eff6ff;">
                                <i class="bi bi-info-circle me-1"></i>
                                <strong>Format kolom yang dikenali otomatis:</strong><br>
                                <code>Select Company</code>, <code>Pick a date</code>, <code>Total Manpower</code>,
                                <code>Total Personal on Board</code>, <code>Informed by</code>, <code>Contact WhatsApp</code>, <code>Email</code>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="submitBtn">
                                <i class="bi bi-upload me-2"></i>Mulai Import
                            </button>
                        </form>

                        {{-- Progress bar (muncul saat submit) --}}
                        <div id="progressWrap" class="mt-3 d-none">
                            <div class="text-center text-muted small mb-2">Sedang memproses data...</div>
                            <div class="progress" style="height:8px;border-radius:99px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                     style="width:100%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Panduan --}}
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-question-circle me-2 text-primary"></i>Panduan Import</h6>

                        <div class="d-flex mb-3">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                                 style="width:28px;height:28px;font-size:.8rem;font-weight:700;">1</div>
                            <div>
                                <div class="fw-semibold small">Baris pertama = Header</div>
                                <div class="text-muted" style="font-size:.8rem;">Sistem otomatis deteksi posisi kolom dari nama header.</div>
                            </div>
                        </div>

                        <div class="d-flex mb-3">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                                 style="width:28px;height:28px;font-size:.8rem;font-weight:700;">2</div>
                            <div>
                                <div class="fw-semibold small">Data duplikat aman</div>
                                <div class="text-muted" style="font-size:.8rem;">Jika perusahaan + tanggal sudah ada, data akan di-<em>update</em>. Tidak ada duplikat.</div>
                            </div>
                        </div>

                        <div class="d-flex mb-3">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                                 style="width:28px;height:28px;font-size:.8rem;font-weight:700;">3</div>
                            <div>
                                <div class="fw-semibold small">Perusahaan baru otomatis dibuat</div>
                                <div class="text-muted" style="font-size:.8rem;">Jika nama perusahaan di Excel belum ada di database, akan dibuat otomatis.</div>
                            </div>
                        </div>

                        <div class="d-flex mb-3">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                                 style="width:28px;height:28px;font-size:.8rem;font-weight:700;">4</div>
                            <div>
                                <div class="fw-semibold small">Format tanggal fleksibel</div>
                                <div class="text-muted" style="font-size:.8rem;">Mendukung format: <code>Y-m-d</code>, <code>d/m/Y</code>, <code>m/d/Y</code>, dan serial number Excel.</div>
                            </div>
                        </div>

                        <hr>

                        <div class="text-muted small">
                            <i class="bi bi-shield-check text-success me-1"></i>
                            File tidak disimpan permanen — hanya diproses lalu dihapus otomatis.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script>
const dropZone   = document.getElementById('dropZone');
const fileInput  = document.getElementById('excel_file');
const fileName   = document.getElementById('fileName');
const submitBtn  = document.getElementById('submitBtn');
const progressWrap = document.getElementById('progressWrap');
const form       = document.getElementById('importForm');

// Preview nama file
fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) {
        fileName.textContent = '📎 ' + fileInput.files[0].name;
        fileName.classList.remove('d-none');
        dropZone.style.borderColor = '#3b82f6';
        dropZone.style.background  = '#eff6ff';
    }
});

// Drag & drop
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = '#3b82f6'; });
dropZone.addEventListener('dragleave', ()  => { dropZone.style.borderColor = '#cbd5e1'; });
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        fileName.textContent = '📎 ' + file.name;
        fileName.classList.remove('d-none');
        dropZone.style.borderColor = '#3b82f6';
        dropZone.style.background  = '#eff6ff';
    }
});

// Submit - tampilkan progress
form.addEventListener('submit', () => {
    if (!fileInput.files[0]) return;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
    progressWrap.classList.remove('d-none');
});
</script>
@endpush