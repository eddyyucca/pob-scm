<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Data Karyawan POB – PT SCM</title>
    <link rel="icon" type="image/x-icon" href="https://tms.scmnickel.com/assets/v2/img/branding/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body{background:#e8f0f7;font-family:'Segoe UI',sans-serif;}
        .wrap{max-width:700px;margin:0 auto;padding:32px 16px 64px;}
        .banner{background:linear-gradient(135deg,#1a3c5e,#0d6efd);border-radius:10px 10px 0 0;padding:24px 32px;color:#fff;}
        .body-card{background:#fff;border-radius:0 0 10px 10px;box-shadow:0 4px 24px rgba(0,0,0,.09);padding:28px 32px;}
        .lang-bar{background:#fff;border-bottom:1px solid #e2e8f0;padding:8px 32px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
        .lang-btn{border:1px solid #cbd5e1;background:#fff;border-radius:20px;padding:3px 14px;font-size:.8rem;cursor:pointer;color:#475569;transition:all .15s;}
        .lang-btn.active{background:#1a3c5e;color:#fff;border-color:#1a3c5e;}
        .step-bar{display:flex;margin-bottom:24px;}
        .step{flex:1;text-align:center;padding:10px 6px;font-size:.78rem;font-weight:600;border-bottom:3px solid #e2e8f0;color:#94a3b8;}
        .step.done{border-bottom-color:#16a34a;color:#16a34a;}
        .step.active{border-bottom-color:#2563eb;color:#2563eb;}
        .drop-zone{border:2px dashed #cbd5e1;border-radius:10px;padding:36px 20px;text-align:center;cursor:pointer;background:#f8fafc;transition:all .2s;}
        .drop-zone:hover,.drop-zone.drag{border-color:#2563eb;background:#eff6ff;}
        .drop-zone.has-file{border-color:#16a34a;background:#f0fdf4;}
        .err-row{font-size:.78rem;padding:4px 0;color:#dc2626;border-bottom:1px solid #fee2e2;}
        .err-row:last-child{border-bottom:none;}
        .mismatch-box{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:16px 20px;margin-bottom:20px;}
        .mismatch-box .num{font-size:2rem;font-weight:700;line-height:1;}
        .preview-wrap{max-height:260px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;}
    </style>
</head>
<body>
<div class="wrap">

{{-- Header --}}
<div class="banner">
    <div class="d-flex align-items-center gap-3 mb-2">
        <img src="https://media.licdn.com/dms/image/v2/D560BAQH-4NKYZuKb5A/company-logo_200_200/B56ZYgesv1HoAI-/0/1744301641123?e=1775692800&v=beta&t=T35fKEIIQZ0XooMkYKRdQPwY91QRxjBheWWmw49cLFU"
             alt="SCM" style="width:40px;height:40px;border-radius:8px;object-fit:cover;">
        <div>
            <div style="font-size:.7rem;opacity:.6;text-transform:uppercase;letter-spacing:1px;">PT Sulawesi Cahaya Mineral</div>
            <div style="font-size:1.1rem;font-weight:700;" id="up-title">Upload Daftar Karyawan Onsite</div>
        </div>
    </div>
    <p style="font-size:.82rem;opacity:.8;margin:0;" id="up-subtitle">Langkah 2 dari 2 — Upload file Excel berisi daftar karyawan yang sedang onsite</p>
</div>

{{-- Language bar --}}
<div class="lang-bar">
    <i class="bi bi-translate" style="color:#64748b;"></i>
    <button class="lang-btn active" onclick="setLang('id')">&#x1F1EE;&#x1F1E9; Indonesia</button>
    <button class="lang-btn" onclick="setLang('en')">&#x1F1EC;&#x1F1E7; English</button>
    <button class="lang-btn" onclick="setLang('zh')">&#x1F1E8;&#x1F1F3; &#x4E2D;&#x6587;</button>
</div>

<div class="body-card">

    {{-- Step bar --}}
    <div class="step-bar">
        <div class="step done"><i class="bi bi-check-circle-fill me-1"></i><span id="step1-text">Isi Data POB</span></div>
        <div class="step active"><i class="bi bi-upload me-1"></i><span id="step2-text">Upload Karyawan</span></div>
    </div>

    {{-- Info laporan --}}
    <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:14px 20px;margin-bottom:20px;">
        <div class="row g-3 text-center">
            <div class="col-4">
                <div style="font-size:.72rem;color:#64748b;" id="lbl-company">Perusahaan</div>
                <div style="font-weight:700;color:#1a3c5e;font-size:.88rem;">{{ $company->name }}</div>
            </div>
            <div class="col-4">
                <div style="font-size:.72rem;color:#64748b;" id="lbl-date">Tanggal</div>
                <div style="font-weight:700;color:#1a3c5e;">{{ \Carbon\Carbon::parse($entry->date)->format('d M Y') }}</div>
            </div>
            <div class="col-4">
                <div style="font-size:.72rem;color:#64748b;" id="lbl-pob">Total POB Dilaporkan</div>
                <div style="font-weight:700;color:#2563eb;font-size:1.4rem;line-height:1;">{{ $entry->total_pob }}</div>
                <div style="font-size:.7rem;color:#64748b;" id="lbl-people">orang</div>
            </div>
        </div>
    </div>

    {{-- ERROR: Jumlah tidak sesuai --}}
    @if(session('pob_mismatch'))
    @php $mm = session('pob_mismatch'); @endphp
    <div class="mismatch-box mb-3">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size:1.2rem;"></i>
            <span style="font-weight:700;color:#dc2626;font-size:.9rem;" id="mismatch-title">Jumlah Karyawan Tidak Sesuai — File Ditolak</span>
        </div>
        <div class="row g-3 text-center mb-3">
            <div class="col-4">
                <div style="font-size:.72rem;color:#64748b;margin-bottom:4px;" id="mm-lbl-expected">Total POB Laporan</div>
                <div class="num text-primary">{{ $mm['expected'] }}</div>
            </div>
            <div class="col-4">
                <div style="font-size:.72rem;color:#64748b;margin-bottom:4px;" id="mm-lbl-uploaded">Data di File Excel</div>
                <div class="num text-danger">{{ $mm['uploaded'] }}</div>
            </div>
            <div class="col-4">
                <div style="font-size:.72rem;color:#64748b;margin-bottom:4px;" id="mm-lbl-diff">Selisih</div>
                <div class="num" style="color:#ea580c;">{{ $mm['diff'] > 0 ? '+'.$mm['diff'] : $mm['diff'] }}</div>
            </div>
        </div>
        <div style="background:#fff;border-radius:8px;padding:10px 14px;font-size:.82rem;color:#7f1d1d;" id="mismatch-hint">
            @if($mm['diff'] > 0)
            File Excel memiliki <strong>{{ $mm['diff'] }} karyawan lebih banyak</strong>. Kurangi <strong>{{ $mm['diff'] }} baris</strong> atau koreksi angka POB.
            @else
            File Excel memiliki <strong>{{ abs($mm['diff']) }} karyawan lebih sedikit</strong>. Tambah <strong>{{ abs($mm['diff']) }} baris</strong> atau koreksi angka POB.
            @endif
        </div>
    </div>
    @endif

    {{-- ERROR: Baris tidak valid --}}
    @if($errors->has('employee_file') && !session('pob_mismatch'))
    <div class="alert alert-danger border-0 mb-3" style="border-radius:8px;font-size:.85rem;">
        <i class="bi bi-x-circle-fill me-2"></i><strong id="file-err-msg">{{ $errors->first('employee_file') }}</strong>
    </div>
    @endif

    @if(session('row_errors') && count(session('row_errors')) > 0)
    <div class="mb-3" style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:14px 16px;">
        <div style="font-weight:700;color:#dc2626;font-size:.85rem;margin-bottom:8px;">
            <i class="bi bi-table me-1"></i>
            <span id="row-err-title">{{ count(session('row_errors')) }} baris bermasalah — perbaiki lalu upload ulang:</span>
        </div>
        <div class="preview-wrap" style="max-height:180px;">
            <div style="padding:8px 12px;">
                @foreach(session('row_errors') as $rowErr)
                <div class="err-row">&#x26A0; {{ $rowErr }}</div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <form action="{{ route('form.upload.post') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
        @csrf

        {{-- Drop zone --}}
        <div class="drop-zone mb-3" id="dropZone" onclick="document.getElementById('employee_file').click()">
            <i class="bi bi-file-earmark-person" style="font-size:2.2rem;color:#94a3b8;display:block;margin-bottom:8px;"></i>
            <div class="fw-semibold text-muted" id="drop-text">Klik atau seret file Excel ke sini</div>
            <div style="font-size:.75rem;color:#94a3b8;" id="drop-hint">.xlsx / .xls — Maksimal 10MB</div>
            <div id="fileName" class="mt-2 d-none" style="font-size:.85rem;color:#2563eb;font-weight:600;"></div>
        </div>
        <input type="file" id="employee_file" name="employee_file" accept=".xlsx,.xls,.csv" class="d-none">

        {{-- Count info --}}
        <div id="previewInfo" class="d-none mb-3 px-3 py-2 rounded d-flex justify-content-between align-items-center"
             style="background:#f8fafc;border:1px solid #e2e8f0;">
            <span style="font-size:.83rem;">
                <i class="bi bi-people me-1"></i>
                <span id="detected-label">Terdeteksi:</span>
                <strong id="previewCount">0</strong>
                <span id="detected-people">karyawan</span>
            </span>
            <span id="matchBadge" style="font-size:.78rem;border-radius:20px;padding:3px 12px;font-weight:600;"></span>
        </div>

        {{-- Preview tabel mini --}}
        <div id="previewWrap" class="d-none mb-3">
            <div style="font-size:.78rem;color:#64748b;margin-bottom:4px;" id="preview-label">Preview (5 baris pertama):</div>
            <div class="preview-wrap">
                <table class="table table-sm mb-0" style="font-size:.75rem;">
                    <thead style="background:#f8fafc;"><tr id="previewHeader"></tr></thead>
                    <tbody id="previewBody"></tbody>
                </table>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('form.index') }}" class="btn btn-outline-secondary" style="border-radius:8px;" id="btn-back">
                <i class="bi bi-arrow-left me-1"></i><span id="back-text">Kembali</span>
            </a>
            <button type="submit" class="btn btn-primary flex-grow-1 fw-semibold" id="btnSubmit"
                    style="border-radius:8px;" disabled>
                <i class="bi bi-cloud-upload me-2"></i>
                <span id="btnText">Upload & Selesai</span>
            </button>
        </div>
    </form>

    <div class="mt-4 pt-3 border-top d-flex justify-content-center gap-3 flex-wrap">
        <a href="{{ route('form.template') }}" class="btn btn-sm btn-outline-success" style="border-radius:20px;">
            <i class="bi bi-file-earmark-excel me-1"></i><span id="tmpl-text">Download Template Excel</span>
        </a>
    </div>
    <div class="text-center mt-2" style="font-size:.72rem;color:#94a3b8;" id="hint-count">
        Jumlah baris di Excel harus sama persis dengan Total POB yang dilaporkan ({{ $entry->total_pob }} orang)
    </div>

</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
const POB_TOTAL = {{ $entry->total_pob }};

// ================================================================
// TERJEMAHAN
// ================================================================
const LANG = {
    id: {
        upTitle      : 'Upload Daftar Karyawan Onsite',
        upSubtitle   : 'Langkah 2 dari 2 — Upload file Excel berisi daftar karyawan yang sedang onsite',
        step1        : 'Isi Data POB',
        step2        : 'Upload Karyawan',
        lblCompany   : 'Perusahaan',
        lblDate      : 'Tanggal',
        lblPob       : 'Total POB Dilaporkan',
        lblPeople    : 'orang',
        mismatchTitle: 'Jumlah Karyawan Tidak Sesuai — File Ditolak',
        mmExpected   : 'Total POB Laporan',
        mmUploaded   : 'Data di File Excel',
        mmDiff       : 'Selisih',
        dropText     : 'Klik atau seret file Excel ke sini',
        dropHint     : '.xlsx / .xls — Maksimal 10MB',
        detectedLbl  : 'Terdeteksi:',
        detectedPpl  : 'karyawan',
        matchOk      : '✔ Sesuai POB',
        matchFail    : 'dari POB',
        previewLbl   : 'Preview (5 baris pertama):',
        backText     : 'Kembali',
        btnUpload    : 'Upload & Selesai',
        btnUploading : 'Upload (akan divalidasi)',
        tmplText     : 'Download Template Excel',
        hintCount    : `Jumlah baris di Excel harus sama persis dengan Total POB yang dilaporkan (${POB_TOTAL} orang)`,
    },
    en: {
        upTitle      : 'Upload Onsite Employee List',
        upSubtitle   : 'Step 2 of 2 — Upload the Excel file containing the list of onsite employees',
        step1        : 'Fill POB Data',
        step2        : 'Upload Employees',
        lblCompany   : 'Company',
        lblDate      : 'Date',
        lblPob       : 'Reported POB Total',
        lblPeople    : 'people',
        mismatchTitle: 'Employee Count Mismatch — File Rejected',
        mmExpected   : 'POB Report Total',
        mmUploaded   : 'Data in Excel File',
        mmDiff       : 'Difference',
        dropText     : 'Click or drag and drop Excel file here',
        dropHint     : '.xlsx / .xls — Max 10MB',
        detectedLbl  : 'Detected:',
        detectedPpl  : 'employees',
        matchOk      : '✔ Matches POB',
        matchFail    : 'vs POB',
        previewLbl   : 'Preview (first 5 rows):',
        backText     : 'Back',
        btnUpload    : 'Upload & Finish',
        btnUploading : 'Upload (will be validated)',
        tmplText     : 'Download Excel Template',
        hintCount    : `Number of rows in Excel must exactly match the reported POB total (${POB_TOTAL} people)`,
    },
    zh: {
        upTitle      : '上传在场员工名单',
        upSubtitle   : '第 2 步，共 2 步 — 上传包含在场员工名单的 Excel 文件',
        step1        : '填写 POB 数据',
        step2        : '上传员工',
        lblCompany   : '公司',
        lblDate      : '日期',
        lblPob       : '已报告 POB 总数',
        lblPeople    : '人',
        mismatchTitle: '员工人数不符 — 文件被拒绝',
        mmExpected   : 'POB 报告总数',
        mmUploaded   : 'Excel 文件数据',
        mmDiff       : '差异',
        dropText     : '点击或拖拽 Excel 文件到此处',
        dropHint     : '.xlsx / .xls — 最大 10MB',
        detectedLbl  : '检测到：',
        detectedPpl  : '名员工',
        matchOk      : '✔ 与 POB 一致',
        matchFail    : '与 POB 不符',
        previewLbl   : '预览（前 5 行）：',
        backText     : '返回',
        btnUpload    : '上传并完成',
        btnUploading : '上传（将由服务器验证）',
        tmplText     : '下载 Excel 模板',
        hintCount    : `Excel 中的行数必须与已报告的 POB 总数完全一致（${POB_TOTAL} 人）`,
    },
};

function setLang(lang) {
    const t = LANG[lang];
    if (!t) return;
    document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
    const activeBtn = document.querySelector(`.lang-btn[onclick="setLang('${lang}')"]`);
    if (activeBtn) activeBtn.classList.add('active');

    const set = (id, v) => { const e = document.getElementById(id); if (e && v !== undefined) e.textContent = v; };
    set('up-title',       t.upTitle);
    set('up-subtitle',    t.upSubtitle);
    set('step1-text',     t.step1);
    set('step2-text',     t.step2);
    set('lbl-company',    t.lblCompany);
    set('lbl-date',       t.lblDate);
    set('lbl-pob',        t.lblPob);
    set('lbl-people',     t.lblPeople);
    set('mismatch-title', t.mismatchTitle);
    set('mm-lbl-expected',t.mmExpected);
    set('mm-lbl-uploaded',t.mmUploaded);
    set('mm-lbl-diff',    t.mmDiff);
    set('drop-text',      t.dropText);
    set('drop-hint',      t.dropHint);
    set('detected-label', t.detectedLbl);
    set('detected-people',t.detectedPpl);
    set('preview-label',  t.previewLbl);
    set('back-text',      t.backText);
    set('btnText',        t.btnUpload);
    set('tmpl-text',      t.tmplText);
    set('hint-count',     t.hintCount);

    // Simpan pilihan
    try { localStorage.setItem('pob_lang', lang); } catch(e) {}
    window._currentLang = lang;
}

// ================================================================
// UPLOAD & PREVIEW
// ================================================================
const fileInput = document.getElementById('employee_file');
const dropZone  = document.getElementById('dropZone');
const btnSubmit = document.getElementById('btnSubmit');

fileInput.addEventListener('change', () => readFile(fileInput.files[0]));
dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('drag'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag'));
dropZone.addEventListener('drop', e => {
    e.preventDefault(); dropZone.classList.remove('drag');
    const f = e.dataTransfer.files[0];
    if (f) { const dt = new DataTransfer(); dt.items.add(f); fileInput.files = dt.files; readFile(f); }
});

function readFile(file) {
    if (!file) return;
    document.getElementById('fileName').textContent = '📎 ' + file.name;
    document.getElementById('fileName').classList.remove('d-none');
    const reader = new FileReader();
    reader.onload = e => {
        try {
            const wb   = XLSX.read(e.target.result, { type:'array' });
            const ws   = wb.Sheets[wb.SheetNames[0]];
            const data = XLSX.utils.sheet_to_json(ws, { header:1, defval:'' });
            processData(data);
        } catch(err) { alert('Error: ' + err.message); }
    };
    reader.readAsArrayBuffer(file);
}

function processData(data) {
    if (!data || data.length < 2) return;
    const header = data[0];
    const rows   = data.slice(1).filter(r => r.some(c => String(c).trim() !== ''));
    const count  = rows.length;
    const t      = LANG[window._currentLang || 'id'];

    document.getElementById('previewInfo').classList.remove('d-none');
    document.getElementById('previewCount').textContent = count;

    const badge = document.getElementById('matchBadge');
    if (count === POB_TOTAL) {
        badge.textContent  = t.matchOk + ' (' + POB_TOTAL + ')';
        badge.style.cssText= 'font-size:.78rem;border-radius:20px;padding:3px 12px;font-weight:600;background:#dcfce7;color:#16a34a;';
        dropZone.classList.add('has-file');
        document.getElementById('btnText').textContent = t.btnUpload + ' (' + count + ')';
    } else {
        const diff = count - POB_TOTAL;
        badge.textContent  = (diff > 0 ? '+' : '') + diff + ' ' + t.matchFail;
        badge.style.cssText= 'font-size:.78rem;border-radius:20px;padding:3px 12px;font-weight:600;background:#fee2e2;color:#dc2626;';
        dropZone.style.borderColor = '#fca5a5';
        document.getElementById('btnText').textContent = t.btnUploading;
    }
    btnSubmit.disabled = false;

    // Preview 5 baris
    document.getElementById('previewHeader').innerHTML =
        '<th style="padding:4px 8px;">#</th>' +
        header.map(h => `<th style="padding:4px 8px;white-space:nowrap;">${h||'–'}</th>`).join('');

    const tb = document.getElementById('previewBody');
    tb.innerHTML = rows.slice(0,5).map((row,i) =>
        `<tr style="background:${i%2===0?'#f8fafc':'#fff'};">
            <td style="padding:3px 8px;color:#94a3b8;">${i+1}</td>
            ${row.map(c=>`<td style="padding:3px 8px;">${c||'<span style="color:#cbd5e1;">—</span>'}</td>`).join('')}
        </tr>`
    ).join('');
    if (rows.length > 5) {
        tb.innerHTML += `<tr><td colspan="${header.length+1}" style="text-align:center;color:#94a3b8;font-size:.72rem;padding:6px;">... +${rows.length-5}</td></tr>`;
    }
    document.getElementById('previewWrap').classList.remove('d-none');
}

// ================================================================
// INIT
// ================================================================
document.addEventListener('DOMContentLoaded', () => {
    try {
        const saved = localStorage.getItem('pob_lang');
        if (saved && LANG[saved]) setLang(saved);
    } catch(e) {}
});
</script>
</body>
</html>