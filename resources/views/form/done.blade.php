<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Selesai – PT SCM</title>
    <link rel="icon" type="image/x-icon" href="https://tms.scmnickel.com/assets/v2/img/branding/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body{background:#e8f0f7;font-family:'Segoe UI',sans-serif;}
        .wrap{max-width:580px;margin:0 auto;padding:48px 16px;}
        .card-done{background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.09);overflow:hidden;}
        .done-header{background:linear-gradient(135deg,#16a34a,#15803d);padding:28px 32px;text-align:center;color:#fff;}
        .done-body{padding:28px 32px;}
        .row-stat{display:flex;justify-content:space-between;align-items:center;padding:11px 0;border-bottom:1px solid #f1f5f9;font-size:.88rem;}
        .row-stat:last-child{border-bottom:none;}
        .lbl{color:#64748b;}
        .val{font-weight:700;color:#1e293b;}
        .sync-box{background:#f8fafc;border-radius:10px;padding:14px 18px;margin:14px 0;}
        .sync-item{display:flex;align-items:center;gap:10px;padding:6px 0;font-size:.85rem;}
        .sync-icon{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.85rem;}
        .lang-bar{background:rgba(255,255,255,.12);padding:8px 20px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:center;}
        .lang-btn{border:1px solid rgba(255,255,255,.4);background:rgba(255,255,255,.1);border-radius:20px;padding:3px 12px;font-size:.75rem;cursor:pointer;color:rgba(255,255,255,.85);transition:all .15s;}
        .lang-btn.active{background:#fff;color:#16a34a;border-color:#fff;font-weight:600;}
    </style>
</head>
<body>
<div class="wrap">

@php
    $r       = $result;
    $dateFmt = \Carbon\Carbon::parse($r['date'])->format('d M Y');
    $cntNew  = (int) $r['new'];
    $cntUpd  = (int) $r['updated'];
    $cntRem  = (int) $r['removed'];
    $cntPob  = (int) $r['total_pob'];
    $cntMp   = (int) $r['total_mp'];
    $cntErr  = count($r['row_errors']);
    $ratio   = $cntMp > 0 ? round($cntPob / $cntMp * 100, 1) : 0;
@endphp

<div class="card-done">

    <div class="done-header">
        <div class="lang-bar mb-3">
            <i class="bi bi-translate" style="opacity:.7;"></i>
            <button class="lang-btn active" onclick="setLang('id')">&#x1F1EE;&#x1F1E9; Indonesia</button>
            <button class="lang-btn" onclick="setLang('en')">&#x1F1EC;&#x1F1E7; English</button>
            <button class="lang-btn" onclick="setLang('zh')">&#x1F1E8;&#x1F1F3; &#x4E2D;&#x6587;</button>
        </div>
        <div style="font-size:3rem;margin-bottom:10px;">&#x2705;</div>
        <h2 style="font-size:1.2rem;font-weight:700;margin-bottom:6px;" id="done-title">Laporan Berhasil Dikirim!</h2>
        <p style="font-size:.82rem;opacity:.85;margin:0;" id="done-subtitle">
            Data POB dan daftar karyawan <strong>{{ $dateFmt }}</strong> telah tersimpan
        </p>
    </div>

    <div class="done-body">

        <div style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;" id="sec-summary">Ringkasan Laporan</div>

        <div class="row-stat">
            <span class="lbl" id="lbl-company">Perusahaan</span>
            <span class="val">{{ $r['company'] }}</span>
        </div>
        <div class="row-stat">
            <span class="lbl" id="lbl-date">Tanggal</span>
            <span class="val">{{ $dateFmt }}</span>
        </div>
        <div class="row-stat">
            <span class="lbl" id="lbl-pob">Total POB Final</span>
            <span class="val" style="color:#2563eb;font-size:1.05rem;">
                {{ $cntPob }} <span id="lbl-people" style="font-size:.8rem;font-weight:400;color:#64748b;">orang</span>
            </span>
        </div>
        <div class="row-stat">
            <span class="lbl" id="lbl-mp">Total Manpower</span>
            <span class="val" style="color:#16a34a;">
                {{ $cntMp }} <span id="lbl-people2" style="font-size:.8rem;font-weight:400;color:#64748b;">orang</span>
            </span>
        </div>
        <div class="row-stat">
            <span class="lbl" id="lbl-ratio">Rasio POB / Manpower</span>
            <span class="val" style="color:#ea580c;">{{ $ratio }}%</span>
        </div>

        <div style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin:18px 0 6px;" id="sec-sync">Sinkronisasi Data Karyawan</div>
        <div class="sync-box">
            @if($cntNew === 0 && $cntUpd === 0 && $cntRem === 0)
            <div class="sync-item">
                <div class="sync-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-check-circle-fill"></i></div>
                <div><div style="font-weight:600;color:#16a34a;" id="sync-all-ok">{{ $cntPob }} karyawan tersimpan</div></div>
            </div>
            @else
                @if($cntNew > 0)
                <div class="sync-item">
                    <div class="sync-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-person-plus-fill"></i></div>
                    <div>
                        <div style="font-weight:600;color:#16a34a;" id="sync-new">+{{ $cntNew }} karyawan baru ditambahkan</div>
                        <div style="font-size:.78rem;color:#64748b;" id="sync-new-sub">Belum ada di data sebelumnya</div>
                    </div>
                </div>
                @endif
                @if($cntUpd > 0)
                <div class="sync-item">
                    <div class="sync-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-person-check-fill"></i></div>
                    <div>
                        <div style="font-weight:600;color:#2563eb;" id="sync-updated">{{ $cntUpd }} data karyawan tetap onsite</div>
                        <div style="font-size:.78rem;color:#64748b;" id="sync-updated-sub">Masih onsite dari sebelumnya</div>
                    </div>
                </div>
                @endif
                @if($cntRem > 0)
                <div class="sync-item">
                    <div class="sync-icon" style="background:#fff7ed;color:#ea580c;"><i class="bi bi-person-dash-fill"></i></div>
                    <div>
                        <div style="font-weight:600;color:#ea580c;" id="sync-removed">{{ $cntRem }} karyawan tidak lagi onsite</div>
                        <div style="font-size:.78rem;color:#64748b;" id="sync-removed-sub">Dihapus dari daftar hari ini</div>
                    </div>
                </div>
                @endif
            @endif
        </div>

        @if($cntErr > 0)
        <div class="alert border-0 py-2 px-3 mb-3" style="background:#fef9c3;border-radius:8px;font-size:.82rem;">
            <div style="font-weight:600;color:#92400e;margin-bottom:4px;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <span id="row-err-msg">{{ $cntErr }} baris diabaikan karena data tidak valid</span>
            </div>
            <details>
                <summary style="cursor:pointer;color:#92400e;font-size:.78rem;" id="see-detail">Lihat detail</summary>
                <div style="margin-top:6px;">
                    @foreach(array_slice($r['row_errors'],0,10) as $e)<div>• {{ $e }}</div>@endforeach
                    @if($cntErr > 10)<div>... +{{ $cntErr - 10 }}</div>@endif
                </div>
            </details>
        </div>
        @endif

        <div style="background:#f0fdf4;border-radius:8px;padding:10px 14px;font-size:.78rem;color:#166534;margin-bottom:20px;" id="history-note">
            <i class="bi bi-calendar-check me-1"></i>
            Data karyawan hari ini tersimpan sebagai histori. Admin dapat melihat siapa yang onsite di tanggal tertentu kapan saja.
        </div>

        <a href="{{ route('form.index') }}" class="btn btn-primary w-100 fw-semibold py-2" style="border-radius:8px;">
            <i class="bi bi-plus-circle me-2"></i><span id="btn-new-text">Kirim Laporan Baru</span>
        </a>

        <div class="text-center mt-3" style="font-size:.72rem;color:#94a3b8;" id="footer-note">
            PT Sulawesi Cahaya Mineral — Sistem POB Harian
        </div>
    </div>
</div>
</div>

<script>
const LANG = {
    id: {
        doneTitle    : 'Laporan Berhasil Dikirim!',
        doneSubtitle : 'Data POB dan daftar karyawan <strong>{{ $dateFmt }}</strong> telah tersimpan',
        secSummary   : 'Ringkasan Laporan',
        lblCompany   : 'Perusahaan', lblDate : 'Tanggal',
        lblPob       : 'Total POB Final', lblPeople : 'orang', lblPeople2 : 'orang',
        lblMp        : 'Total Manpower',  lblRatio  : 'Rasio POB / Manpower',
        secSync      : 'Sinkronisasi Data Karyawan',
        syncNew      : '+{{ $cntNew }} karyawan baru ditambahkan',
        syncNewSub   : 'Belum ada di data sebelumnya',
        syncUpdated  : '{{ $cntUpd }} data karyawan tetap onsite',
        syncUpdSub   : 'Masih onsite dari sebelumnya',
        syncRemoved  : '{{ $cntRem }} karyawan tidak lagi onsite',
        syncRemSub   : 'Dihapus dari daftar hari ini',
        syncAllOk    : '{{ $cntPob }} karyawan tersimpan',
        rowErrMsg    : '{{ $cntErr }} baris diabaikan karena data tidak valid',
        seeDetail    : 'Lihat detail',
        historyNote  : 'Data karyawan hari ini tersimpan sebagai histori. Admin dapat melihat siapa yang onsite di tanggal tertentu kapan saja.',
        btnNewText   : 'Kirim Laporan Baru',
        footerNote   : 'PT Sulawesi Cahaya Mineral — Sistem POB Harian',
    },
    en: {
        doneTitle    : 'Report Submitted Successfully!',
        doneSubtitle : 'POB data and employee list for <strong>{{ $dateFmt }}</strong> have been saved',
        secSummary   : 'Report Summary',
        lblCompany   : 'Company',        lblDate    : 'Date',
        lblPob       : 'Final POB Total',lblPeople  : 'people', lblPeople2 : 'people',
        lblMp        : 'Total Manpower', lblRatio   : 'POB / Manpower Ratio',
        secSync      : 'Employee Data Synchronization',
        syncNew      : '+{{ $cntNew }} new employees added',
        syncNewSub   : 'Not in previous data',
        syncUpdated  : '{{ $cntUpd }} employees remain onsite',
        syncUpdSub   : 'Still onsite from before',
        syncRemoved  : '{{ $cntRem }} employees no longer onsite',
        syncRemSub   : "Removed from today's list",
        syncAllOk    : '{{ $cntPob }} employees saved',
        rowErrMsg    : '{{ $cntErr }} rows skipped due to invalid data',
        seeDetail    : 'View details',
        historyNote  : "Today's employee data is saved as history. Admin can view who was onsite on any specific date.",
        btnNewText   : 'Submit Another Report',
        footerNote   : 'PT Sulawesi Cahaya Mineral — Daily POB System',
    },
    zh: {
        doneTitle    : '报告提交成功！',
        doneSubtitle : '<strong>{{ $dateFmt }}</strong> 的 POB 数据和员工名单已保存',
        secSummary   : '报告摘要',
        lblCompany   : '公司',       lblDate    : '日期',
        lblPob       : 'POB 最终总数',lblPeople  : '人', lblPeople2 : '人',
        lblMp        : '总人力',      lblRatio   : 'POB / 人力比率',
        secSync      : '员工数据同步',
        syncNew      : '+{{ $cntNew }} 名新员工已添加',
        syncNewSub   : '之前数据中没有',
        syncUpdated  : '{{ $cntUpd }} 名员工仍在现场',
        syncUpdSub   : '之前已在现场',
        syncRemoved  : '{{ $cntRem }} 名员工已离场',
        syncRemSub   : '已从今日名单中删除',
        syncAllOk    : '{{ $cntPob }} 名员工已保存',
        rowErrMsg    : '{{ $cntErr }} 行因数据无效被跳过',
        seeDetail    : '查看详情',
        historyNote  : '今日员工数据已作为历史记录保存，管理员可随时查看特定日期的在场人员。',
        btnNewText   : '提交另一份报告',
        footerNote   : 'PT Sulawesi Cahaya Mineral — 每日 POB 系统',
    },
};

function setLang(lang) {
    const t = LANG[lang];
    if (!t) return;

    document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
    const btn = document.querySelector(`.lang-btn[onclick="setLang('${lang}')"]`);
    if (btn) btn.classList.add('active');

    const set  = (id, v) => { const e = document.getElementById(id); if (e && v !== undefined) e.textContent = v; };
    const setH = (id, v) => { const e = document.getElementById(id); if (e && v !== undefined) e.innerHTML   = v; };

    set('done-title',      t.doneTitle);
    setH('done-subtitle',  t.doneSubtitle);
    set('sec-summary',     t.secSummary);
    set('lbl-company',     t.lblCompany);
    set('lbl-date',        t.lblDate);
    set('lbl-pob',         t.lblPob);
    set('lbl-people',      t.lblPeople);
    set('lbl-people2',     t.lblPeople2);
    set('lbl-mp',          t.lblMp);
    set('lbl-ratio',       t.lblRatio);
    set('sec-sync',        t.secSync);
    set('sync-new',        t.syncNew);
    set('sync-new-sub',    t.syncNewSub);
    set('sync-updated',    t.syncUpdated);
    set('sync-updated-sub',t.syncUpdSub);
    set('sync-removed',    t.syncRemoved);
    set('sync-removed-sub',t.syncRemSub);
    set('sync-all-ok',     t.syncAllOk);
    set('row-err-msg',     t.rowErrMsg);
    set('see-detail',      t.seeDetail);
    set('history-note',    t.historyNote);
    set('btn-new-text',    t.btnNewText);
    set('footer-note',     t.footerNote);

    try { localStorage.setItem('pob_lang', lang); } catch(e) {}
}

document.addEventListener('DOMContentLoaded', () => {
    try {
        const saved = localStorage.getItem('pob_lang');
        if (saved && LANG[saved]) setLang(saved);
    } catch(e) {}
});
</script>
</body>
</html>