<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Reporting MP & POB – PT Sulawesi Cahaya Mineral</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background:#e8f0f7; font-family:'Segoe UI',sans-serif; }
        .form-wrapper { max-width:680px; margin:0 auto; padding:32px 16px 64px; }
        .form-header-banner {
            background:linear-gradient(135deg,#1a3c5e 0%,#0d6efd 100%);
            border-radius:8px 8px 0 0; padding:28px 32px 20px; color:#fff;
        }
        .form-header-banner h1 { font-size:1.35rem; font-weight:700; margin-bottom:6px; }
        .form-header-banner p { font-size:.85rem; opacity:.85; margin:0; }
        .lang-bar {
            background:#fff; border-bottom:1px solid #e2e8f0;
            padding:8px 32px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;
        }
        .lang-btn {
            border:1px solid #cbd5e1; background:#fff; border-radius:20px;
            padding:3px 14px; font-size:.8rem; cursor:pointer; color:#475569; transition:all .15s;
        }
        .lang-btn.active { background:#1a3c5e; color:#fff; border-color:#1a3c5e; }
        .form-body {
            background:#fff; padding:28px 32px;
            border-radius:0 0 8px 8px; box-shadow:0 4px 24px rgba(0,0,0,.08);
        }
        .q-block {
            border-left:3px solid #1a3c5e; padding:16px 20px; margin-bottom:20px;
            background:#f8fafc; border-radius:0 8px 8px 0; transition:border-color .2s;
        }
        .q-block:focus-within { border-left-color:#0d6efd; background:#eff6ff; }
        .q-label { font-size:.95rem; font-weight:600; color:#1e293b; margin-bottom:4px; }
        .q-desc { font-size:.78rem; color:#64748b; margin-bottom:10px; line-height:1.5; }
        .q-num { font-size:.75rem; color:#94a3b8; margin-bottom:2px; }
        .form-control,.form-select { border:1px solid #cbd5e1; border-radius:6px; font-size:.9rem; padding:10px 12px; }
        .form-control:focus,.form-select:focus { border-color:#0d6efd; box-shadow:0 0 0 3px rgba(13,110,253,.12); }
        .stat-preview {
            background:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%);
            border:1px solid #bae6fd; border-radius:10px; padding:16px 20px; margin:20px 0;
        }
        .stat-box { text-align:center; padding:8px; }
        .stat-box .val { font-size:1.8rem; font-weight:700; line-height:1; }
        .stat-box .lbl { font-size:.72rem; color:#64748b; margin-top:2px; }
        .btn-submit {
            background:#1a3c5e; border:none; color:#fff; padding:12px 32px;
            border-radius:6px; font-size:.95rem; font-weight:600;
            width:100%; cursor:pointer; transition:background .2s;
        }
        .btn-submit:hover { background:#0d6efd; }
        .required-star { color:#e53e3e; margin-left:2px; }
        .footer-note { text-align:center; font-size:.75rem; color:#94a3b8; margin-top:20px; }
        .success-card {
            background:#fff; border-radius:12px; box-shadow:0 4px 24px rgba(0,0,0,.08);
            padding:48px 32px; text-align:center;
        }
    </style>
</head>
<body>
<div class="form-wrapper">

@if(session('success'))
<div class="success-card">
    <div style="font-size:4rem;margin-bottom:16px;">&#x2705;</div>
    <h4 class="fw-bold mb-2" style="color:#1a3c5e;" id="success-title">Laporan Berhasil Dikirim!</h4>
    <p class="text-muted mb-4">{{ session('success') }}</p>
    <a href="/" class="btn btn-primary px-4" style="background:#1a3c5e;border-color:#1a3c5e;" id="btn-back">
        <i class="bi bi-plus-circle me-2"></i><span id="btn-back-text">Kirim Laporan Lain</span>
    </a>
    <div class="mt-3 text-muted small" id="success-sub">Data telah tercatat di sistem PT Sulawesi Cahaya Mineral</div>
</div>
@else

<div class="form-header-banner">
    <div class="d-flex align-items-center mb-3" style="gap:12px;">
        <div style="background:rgba(255,255,255,.15);border-radius:8px;padding:8px 12px;font-size:1.5rem;">&#x26CF;</div>
        <div>
            <div style="font-size:.75rem;opacity:.7;letter-spacing:1px;text-transform:uppercase;">PT Sulawesi Cahaya Mineral</div>
            <h1 id="form-title">Daily Reporting MP & POB</h1>
        </div>
    </div>
    <p id="form-subtitle">Pastikan data yang Anda kirimkan sudah benar karena akan digunakan untuk manajemen rapat mingguan setiap hari Selasa.</p>
    <div style="margin-top:12px;font-size:.78rem;opacity:.7;" id="form-notice">* Wajib diisi</div>
</div>

<div class="lang-bar">
    <i class="bi bi-translate" style="color:#64748b;"></i>
    <button class="lang-btn active" onclick="setLang('id')">&#x1F1EE;&#x1F1E9; Indonesia</button>
    <button class="lang-btn" onclick="setLang('en')">&#x1F1EC;&#x1F1E7; English</button>
    <button class="lang-btn" onclick="setLang('zh')">&#x1F1E8;&#x1F1F3; &#x4E2D;&#x6587;</button>
</div>

<div class="form-body">

    @if($errors->any())
    <div class="alert alert-danger border-0 mb-4" style="border-radius:8px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <span id="err-label">Harap periksa isian berikut:</span>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('form.store') }}" method="POST" novalidate>
        @csrf

        {{-- Q1 --}}
        <div class="q-block">
            <div class="q-num" id="q1-num">Pertanyaan 1</div>
            <div class="q-label"><span id="q1-label">Pilih Perusahaan</span> <span class="required-star">*</span></div>
            <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                <option value="" id="q1-ph">-- Pilih jawaban Anda --</option>
                @foreach($companies as $company)
                <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                @endforeach
            </select>
            @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Q2 --}}
        <div class="q-block">
            <div class="q-num" id="q2-num">Pertanyaan 2</div>
            <div class="q-label"><span id="q2-label">Pilih Tanggal Laporan</span> <span class="required-star">*</span></div>
            <input type="date" name="date"
                class="form-control @error('date') is-invalid @enderror"
                value="{{ old('date', now()->toDateString()) }}"
                max="{{ now()->toDateString() }}" required>
            @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Q3 --}}
        <div class="q-block">
            <div class="q-num" id="q3-num">Pertanyaan 3</div>
            <div class="q-label"><span id="q3-label">Total Manpower</span> <span class="required-star">*</span></div>
            <div class="q-desc" id="q3-desc">Total Manpower adalah jumlah karyawan (cuti + onsite) yang penempatan area kerjanya di area IUP PT SCM sampai dengan Project Area MHR, Representative Office Kendari dan Morowali sedangkan karyawan yang area kerjanya berlokasi di Head Office (Jakarta/Balikpapan, dll) tidak perlu ditambahkan.</div>
            <input type="number" name="total_manpower"
                class="form-control @error('total_manpower') is-invalid @enderror"
                value="{{ old('total_manpower') }}" min="0" id="input-mp" placeholder="0" required>
            @error('total_manpower')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Q4 --}}
        <div class="q-block">
            <div class="q-num" id="q4-num">Pertanyaan 4</div>
            <div class="q-label"><span id="q4-label">Total Personal on Board (Jumlah Karyawan Onsite)</span> <span class="required-star">*</span></div>
            <div class="q-desc" id="q4-desc">Total POB (Personal on Board) adalah jumlah karyawan yang sedang berada di area IUP PT SCM sampai dengan MHR saja, sedangkan karyawan yang bekerja di representative office kendari dan morowali tidak perlu ditambahkan.</div>
            <input type="number" name="total_pob"
                class="form-control @error('total_pob') is-invalid @enderror"
                value="{{ old('total_pob') }}" min="0" id="input-pob" placeholder="0" required>
            @error('total_pob')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Preview --}}
        <div class="stat-preview" id="stat-preview" style="display:none;">
            <div class="row g-0">
                <div class="col-4 stat-box border-end">
                    <div class="val text-primary" id="prev-mp">0</div>
                    <div class="lbl" id="prev-mp-lbl">Total Manpower</div>
                </div>
                <div class="col-4 stat-box border-end">
                    <div class="val text-success" id="prev-pob">0</div>
                    <div class="lbl" id="prev-pob-lbl">Total POB</div>
                </div>
                <div class="col-4 stat-box">
                    <div class="val text-warning" id="prev-ratio">-</div>
                    <div class="lbl" id="prev-ratio-lbl">Rasio POB/MP</div>
                </div>
            </div>
        </div>

        {{-- Q5 --}}
        <div class="q-block">
            <div class="q-num" id="q5-num">Pertanyaan 5</div>
            <div class="q-label"><span id="q5-label">Informed by (Nama Lengkap)</span> <span class="required-star">*</span></div>
            <input type="text" name="informed_by"
                class="form-control @error('informed_by') is-invalid @enderror"
                value="{{ old('informed_by') }}"
                id="input-informed" placeholder="Masukkan nama lengkap Anda" required>
            @error('informed_by')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Q6 --}}
        <div class="q-block">
            <div class="q-num" id="q6-num">Pertanyaan 6</div>
            <div class="q-label"><span id="q6-label">Contact WhatsApp</span> <span class="required-star">*</span></div>
            <input type="text" name="contact_wa"
                class="form-control @error('contact_wa') is-invalid @enderror"
                value="{{ old('contact_wa') }}"
                id="input-wa" placeholder="08xxxxxxxxxx" required>
            @error('contact_wa')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn-submit">
            <i class="bi bi-send-fill me-2"></i><span id="btn-submit-text">Kirim</span>
        </button>
    </form>

    <div class="footer-note mt-4" id="footer-note">
        Jangan pernah memberitahukan kata sandi Anda.<br>
        Data yang Anda serahkan akan dikirim ke pemilik formulir.
    </div>
</div>
@endif
</div>

<script>
const LANG = {
    id: {
        formTitle:'Daily Reporting MP & POB',
        formSubtitle:'Pastikan data yang Anda kirimkan sudah benar karena akan digunakan untuk manajemen rapat mingguan setiap hari Selasa.',
        formNotice:'* Wajib diisi',
        q1num:'Pertanyaan 1',q1label:'Pilih Perusahaan',q1ph:'-- Pilih jawaban Anda --',
        q2num:'Pertanyaan 2',q2label:'Pilih Tanggal Laporan',
        q3num:'Pertanyaan 3',q3label:'Total Manpower',
        q3desc:'Total Manpower adalah jumlah karyawan (cuti + onsite) yang penempatan area kerjanya di area IUP PT SCM sampai dengan Project Area MHR, Representative Office Kendari dan Morowali sedangkan karyawan yang area kerjanya berlokasi di Head Office (Jakarta/Balikpapan, dll) tidak perlu ditambahkan.',
        q4num:'Pertanyaan 4',q4label:'Total Personal on Board (Jumlah Karyawan Onsite)',
        q4desc:'Total POB (Personal on Board) adalah jumlah karyawan yang sedang berada di area IUP PT SCM sampai dengan MHR saja, sedangkan karyawan yang bekerja di representative office kendari dan morowali tidak perlu ditambahkan.',
        q5num:'Pertanyaan 5',q5label:'Informed by (Nama Lengkap)',q5ph:'Masukkan nama lengkap Anda',
        q6num:'Pertanyaan 6',q6label:'Contact WhatsApp',q6ph:'08xxxxxxxxxx',
        prevMpLbl:'Total Manpower',prevPobLbl:'Total POB',prevRatioLbl:'Rasio POB/MP',
        btnSubmit:'Kirim',
        footerNote:'Jangan pernah memberitahukan kata sandi Anda.<br>Data yang Anda serahkan akan dikirim ke pemilik formulir.',
        successTitle:'Laporan Berhasil Dikirim!',successSub:'Data telah tercatat di sistem PT Sulawesi Cahaya Mineral',btnBack:'Kirim Laporan Lain',
    },
    en: {
        formTitle:'Daily Reporting MP & POB',
        formSubtitle:'Please ensure the data you submit is correct as it will be used for weekly meeting management on every Tuesday.',
        formNotice:'* Required',
        q1num:'Question 1',q1label:'Select Company',q1ph:'-- Choose your answer --',
        q2num:'Question 2',q2label:'Pick a Date',
        q3num:'Question 3',q3label:'Total Manpower',
        q3desc:'Total Manpower is the number of employees (on leave + onsite) whose work area is in the IUP PT SCM area up to Project Area MHR, Representative Office Kendari and Morowali. Employees working at Head Office (Jakarta/Balikpapan, etc.) do not need to be included.',
        q4num:'Question 4',q4label:'Total Personal on Board (Total Onsite Employees)',
        q4desc:'Total POB (Personal on Board) is the number of employees currently in the IUP PT SCM area up to MHR only. Employees working at the representative office in Kendari and Morowali do not need to be included.',
        q5num:'Question 5',q5label:'Informed by (Full Name)',q5ph:'Enter your full name',
        q6num:'Question 6',q6label:'Contact WhatsApp',q6ph:'08xxxxxxxxxx',
        prevMpLbl:'Total Manpower',prevPobLbl:'Total POB',prevRatioLbl:'POB/MP Ratio',
        btnSubmit:'Submit',
        footerNote:"Never share your password.<br>Your submitted data will be sent to the form owner.",
        successTitle:'Report Submitted Successfully!',successSub:'Data has been recorded in the PT Sulawesi Cahaya Mineral system',btnBack:'Submit Another Report',
    },
    zh: {
        formTitle:'MP & POB 每日报告',
        formSubtitle:'请确保您提交的数据正确无误，因为这些数据将用于每周二的会议管理。',
        formNotice:'* 必填',
        q1num:'问题 1',q1label:'选择公司',q1ph:'-- 请选择您的答案 --',
        q2num:'问题 2',q2label:'选择日期',
        q3num:'问题 3',q3label:'总人力',
        q3desc:'总人力是指工作区域在 PT SCM IUP 区域至 MHR 项目区域、Kendari 和 Morowali 代表处的员工总数（含休假+在场）。在雅加达/巴厘巴板等总部工作的员工无需计入。',
        q4num:'问题 4',q4label:'在场人员总数（驻场员工人数）',
        q4desc:'总 POB（在场人员）是指目前仅位于 PT SCM IUP 区域至 MHR 的员工人数。在 Kendari 和 Morowali 代表处工作的员工无需计入。',
        q5num:'问题 5',q5label:'汇报人（全名）',q5ph:'请输入您的全名',
        q6num:'问题 6',q6label:'WhatsApp 联系方式',q6ph:'08xxxxxxxxxx',
        prevMpLbl:'总人力',prevPobLbl:'总 POB',prevRatioLbl:'POB/MP 比率',
        btnSubmit:'提交',
        footerNote:'请勿透露您的密码。<br>您提交的数据将发送给表单所有者。',
        successTitle:'报告提交成功！',successSub:'数据已记录在 PT Sulawesi Cahaya Mineral 系统中',btnBack:'提交另一份报告',
    },
};

function setLang(lang){
    const t=LANG[lang];
    document.querySelectorAll('.lang-btn').forEach(b=>b.classList.remove('active'));
    document.querySelector('.lang-btn[onclick="setLang(\''+lang+'\')"]').classList.add('active');
    const set=(id,v)=>{const e=document.getElementById(id);if(e&&v!==undefined)e.textContent=v;};
    const setH=(id,v)=>{const e=document.getElementById(id);if(e&&v!==undefined)e.innerHTML=v;};
    const setP=(id,v)=>{const e=document.getElementById(id);if(e&&v!==undefined)e.placeholder=v;};
    set('form-title',t.formTitle); set('form-subtitle',t.formSubtitle); set('form-notice',t.formNotice);
    for(let i=1;i<=6;i++){set('q'+i+'-num',t['q'+i+'num']);set('q'+i+'-label',t['q'+i+'label']);}
    set('q3-desc',t.q3desc); set('q4-desc',t.q4desc);
    const selPh=document.getElementById('q1-ph'); if(selPh)selPh.textContent=t.q1ph;
    setP('input-informed',t.q5ph); setP('input-wa',t.q6ph);
    set('prev-mp-lbl',t.prevMpLbl); set('prev-pob-lbl',t.prevPobLbl); set('prev-ratio-lbl',t.prevRatioLbl);
    set('btn-submit-text',t.btnSubmit); setH('footer-note',t.footerNote);
    set('success-title',t.successTitle); set('success-sub',t.successSub); set('btn-back-text',t.btnBack);
}

// Preview stat
const mpInput=document.getElementById('input-mp');
const pobInput=document.getElementById('input-pob');
const preview=document.getElementById('stat-preview');
function updatePreview(){
    const mp=parseInt(mpInput?.value)||0;
    const pob=parseInt(pobInput?.value)||0;
    if(mp>0||pob>0){
        preview.style.display='block';
        document.getElementById('prev-mp').textContent=mp.toLocaleString();
        document.getElementById('prev-pob').textContent=pob.toLocaleString();
        document.getElementById('prev-ratio').textContent=mp>0?((pob/mp)*100).toFixed(1)+'%':'-';
    }else{preview.style.display='none';}
}
mpInput?.addEventListener('input',updatePreview);
pobInput?.addEventListener('input',updatePreview);
</script>
</body>
</html>
