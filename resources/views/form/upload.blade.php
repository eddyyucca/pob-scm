<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Data Karyawan POB – PT SCM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body{background:#e8f0f7;font-family:'Segoe UI',sans-serif;}
        .wrap{max-width:700px;margin:0 auto;padding:32px 16px 64px;}
        .banner{background:linear-gradient(135deg,#1a3c5e,#0d6efd);border-radius:10px 10px 0 0;padding:24px 32px;color:#fff;}
        .body{background:#fff;border-radius:0 0 10px 10px;box-shadow:0 4px 24px rgba(0,0,0,.09);padding:28px 32px;}
        .step-bar{display:flex;align-items:center;gap:0;margin-bottom:28px;}
        .step{flex:1;text-align:center;padding:10px 6px;font-size:.78rem;font-weight:600;border-bottom:3px solid #e2e8f0;color:#94a3b8;}
        .step.done{border-bottom-color:#16a34a;color:#16a34a;}
        .step.active{border-bottom-color:#2563eb;color:#2563eb;}
        .drop-zone{border:2px dashed #cbd5e1;border-radius:10px;padding:40px 20px;text-align:center;cursor:pointer;background:#f8fafc;transition:all .2s;}
        .drop-zone:hover,.drop-zone.drag{border-color:#2563eb;background:#eff6ff;}
        .preview-table{font-size:.8rem;max-height:260px;overflow-y:auto;}
        .err-item{font-size:.78rem;padding:3px 0;color:#dc2626;}
        .badge-mp{background:#1a3c5e;color:#fff;border-radius:20px;padding:2px 12px;font-size:.78rem;}
    </style>
</head>
<body>
<div class="wrap">

    {{-- Banner --}}
    <div class="banner">
        <div style="font-size:.72rem;opacity:.6;text-transform:uppercase;letter-spacing:1px;">PT Sulawesi Cahaya Mineral</div>
        <h1 style="font-size:1.2rem;font-weight:700;margin:4px 0;">Upload Daftar Karyawan Onsite</h1>
        <p style="font-size:.82rem;opacity:.8;margin:0;">Langkah 2 dari 2 – Upload file Excel berisi daftar karyawan yang sedang onsite</p>
    </div>

    <div class="body">
        {{-- Step bar --}}
        <div class="step-bar">
            <div class="step done"><i class="bi bi-check-circle-fill me-1"></i>Isi Data POB</div>
            <div class="step active"><i class="bi bi-upload me-1"></i>Upload Karyawan</div>
        </div>

        {{-- Info laporan --}}
        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:16px 20px;margin-bottom:24px;">
            <div class="row g-3 text-center">
                <div class="col-4">
                    <div style="font-size:.72rem;color:#64748b;">Perusahaan</div>
                    <div style="font-weight:700;color:#1a3c5e;font-size:.88rem;">{{ $company->name }}</div>
                </div>
                <div class="col-4">
                    <div style="font-size:.72rem;color:#64748b;">Tanggal</div>
                    <div style="font-weight:700;color:#1a3c5e;">{{ \Carbon\Carbon::parse($entry->date)->format('d M Y') }}</div>
                </div>
                <div class="col-4">
                    <div style="font-size:.72rem;color:#64748b;">Total POB Dilaporkan</div>
                    <div style="font-weight:700;color:#2563eb;font-size:1.2rem;">{{ $entry->total_pob }}</div>
                </div>
            </div>
        </div>

        {{-- Errors --}}
        @if($errors->any())
        <div class="alert alert-danger border-0 mb-3" style="border-radius:8px;font-size:.85rem;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>{{ $errors->first('employee_file') }}</strong>
        </div>
        @endif

        @if(session('row_errors') && count(session('row_errors')) > 0)
        <div class="alert alert-warning border-0 mb-3" style="border-radius:8px;">
            <div class="fw-semibold mb-1" style="font-size:.85rem;"><i class="bi bi-exclamation-triangle me-1"></i>{{ count(session('row_errors')) }} baris diabaikan karena error:</div>
            @foreach(array_slice(session('row_errors'), 0, 10) as $e)
            <div class="err-item">• {{ $e }}</div>
            @endforeach
            @if(count(session('row_errors')) > 10)
            <div class="err-item">... dan {{ count(session('row_errors')) - 10 }} lainnya</div>
            @endif
        </div>
        @endif

        <form action="{{ route('form.upload.post') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
            @csrf

            {{-- Drop zone --}}
            <div class="drop-zone mb-3" id="dropZone" onclick="document.getElementById('employee_file').click()">
                <i class="bi bi-file-earmark-person" style="font-size:2.5rem;color:#94a3b8;display:block;margin-bottom:8px;"></i>
                <div class="fw-semibold text-muted">Klik atau seret file Excel ke sini</div>
                <div style="font-size:.78rem;color:#94a3b8;">.xlsx / .xls — Maksimal 10MB</div>
                <div id="fileName" class="mt-2 d-none" style="font-size:.85rem;color:#2563eb;font-weight:600;"></div>
            </div>
            <input type="file" id="employee_file" name="employee_file" accept=".xlsx,.xls,.csv" class="d-none">

            {{-- Preview area --}}
            <div id="previewWrap" class="d-none mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div style="font-size:.82rem;font-weight:600;color:#374151;"><i class="bi bi-table me-1"></i>Preview Data <span id="previewCount" class="badge-mp ms-1"></span></div>
                    <div id="countCheck" style="font-size:.78rem;"></div>
                </div>
                <div class="preview-table border rounded">
                    <table class="table table-sm table-hover mb-0" style="font-size:.78rem;">
                        <thead style="background:#f8fafc;position:sticky;top:0;">
                            <tr id="previewHeader"></tr>
                        </thead>
                        <tbody id="previewBody"></tbody>
                    </table>
                </div>
                <div id="previewErrors" class="mt-2"></div>
            </div>

            {{-- Tombol --}}
            <div class="d-flex gap-2">
                <a href="{{ route('form.index') }}" class="btn btn-outline-secondary" style="border-radius:8px;">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
                <button type="submit" class="btn btn-primary flex-grow-1 fw-semibold" id="btnSubmit" style="border-radius:8px;" disabled>
                    <i class="bi bi-cloud-upload me-2"></i>Upload & Selesai
                </button>
            </div>
        </form>

        {{-- Download template --}}
        <div class="mt-4 pt-3 border-top text-center">
            <a href="{{ route('form.template') }}" class="btn btn-sm btn-outline-success" style="border-radius:20px;">
                <i class="bi bi-download me-1"></i>Download Template Excel
            </a>
            <div style="font-size:.72rem;color:#94a3b8;margin-top:6px;">Gunakan template ini agar format kolom sesuai</div>
        </div>
    </div>
</div>

<script>
const POB_TOTAL = {{ $entry->total_pob }};
const fileInput = document.getElementById('employee_file');
const dropZone  = document.getElementById('dropZone');
const fileName  = document.getElementById('fileName');
const btnSubmit = document.getElementById('btnSubmit');
const previewWrap = document.getElementById('previewWrap');

fileInput.addEventListener('change', () => readFile(fileInput.files[0]));

dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag');
    const f = e.dataTransfer.files[0];
    if (f) { const dt = new DataTransfer(); dt.items.add(f); fileInput.files = dt.files; readFile(f); }
});

function readFile(file) {
    if (!file) return;
    fileName.textContent = '📎 ' + file.name;
    fileName.classList.remove('d-none');

    // Pakai SheetJS untuk preview
    const reader = new FileReader();
    reader.onload = e => {
        try {
            const wb   = XLSX.read(e.target.result, {type:'array'});
            const ws   = wb.Sheets[wb.SheetNames[0]];
            const data = XLSX.utils.sheet_to_json(ws, {header:1, defval:''});
            showPreview(data);
        } catch(err) {
            document.getElementById('previewErrors').innerHTML = '<div class="alert alert-danger py-2 px-3 small">Gagal membaca file: '+err.message+'</div>';
            previewWrap.classList.remove('d-none');
        }
    };
    reader.readAsArrayBuffer(file);
}

function showPreview(data) {
    if (!data || data.length < 2) return;

    const header = data[0];
    const rows   = data.slice(1).filter(r => r.some(c => c !== ''));
    const maxPreview = 50;

    // Header
    const thRow = document.getElementById('previewHeader');
    thRow.innerHTML = '<th style="padding:4px 8px;">#</th>' +
        header.map(h => `<th style="padding:4px 8px;white-space:nowrap;">${h||'-'}</th>`).join('');

    // Body
    const tb = document.getElementById('previewBody');
    tb.innerHTML = rows.slice(0, maxPreview).map((row, i) => {
        const name  = row[findCol(header,'nama','name')] || '';
        const empty = !name.trim();
        const bg    = empty ? '#fef2f2' : (i%2===0?'#f8fafc':'#fff');
        return `<tr style="background:${bg};">
            <td style="padding:3px 8px;color:#94a3b8;">${i+1}</td>
            ${row.map(c=>`<td style="padding:3px 8px;">${c||'<span style="color:#cbd5e1;">—</span>'}</td>`).join('')}
        </tr>`;
    }).join('');

    if (rows.length > maxPreview) {
        tb.innerHTML += `<tr><td colspan="${header.length+1}" style="text-align:center;color:#94a3b8;font-size:.75rem;padding:6px;">... dan ${rows.length - maxPreview} baris lainnya</td></tr>`;
    }

    // Count check
    const countEl = document.getElementById('previewCount');
    countEl.textContent = rows.length + ' karyawan';

    const checkEl = document.getElementById('countCheck');
    if (rows.length === POB_TOTAL) {
        checkEl.innerHTML = `<span style="color:#16a34a;font-weight:600;"><i class="bi bi-check-circle-fill me-1"></i>Sesuai POB (${POB_TOTAL})</span>`;
    } else {
        checkEl.innerHTML = `<span style="color:#ea580c;font-weight:600;"><i class="bi bi-exclamation-triangle me-1"></i>File: ${rows.length} | POB: ${POB_TOTAL} — berbeda, POB akan diperbarui</span>`;
    }

    previewWrap.classList.remove('d-none');
    btnSubmit.disabled = false;
}

function findCol(header, ...keys) {
    for (const h of header) {
        const hl = String(h).toLowerCase();
        for (const k of keys) { if (hl.includes(k)) return header.indexOf(h); }
    }
    return -1;
}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</body>
</html>
