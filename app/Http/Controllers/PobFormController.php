<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PobEmployee;
use App\Models\PobEntry;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PobFormController extends Controller
{
    // ─────────────────────────────────────────────
    // STEP 1: Tampilkan form isian POB
    // ─────────────────────────────────────────────
    public function index()
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        return view('form.index', compact('companies'));
    }

    // ─────────────────────────────────────────────
    // STEP 1: Validasi & simpan ke SESSION saja
    //         Belum masuk database
    // ─────────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id'     => 'required|exists:companies,id',
            'date'           => 'required|date|before_or_equal:today',
            'total_pob'      => 'required|integer|min:1',
            'total_manpower' => 'required|integer|min:1|gte:total_pob',
            'informed_by'    => 'required|string|max:100',
            'contact_wa'     => 'required|string|max:30',
        ], [
            'total_pob.min'      => 'Total POB minimal 1 orang.',
            'total_manpower.min' => 'Total Manpower minimal 1 orang.',
            'total_manpower.gte' => 'Total Manpower tidak boleh kurang dari Total POB.',
        ]);

        // Simpan ke session — BELUM ke database
        session([
            'pob_company_id'     => (int) $validated['company_id'],
            'pob_date'           => $validated['date'],
            'pob_total'          => (int) $validated['total_pob'],
            'pob_total_manpower' => (int) $validated['total_manpower'],
            'pob_informed_by'    => $validated['informed_by'],
            'pob_contact_wa'     => $validated['contact_wa'],
        ]);

        return redirect()->route('form.upload');
    }

    // ─────────────────────────────────────────────
    // STEP 2: Tampilkan halaman upload Excel
    // ─────────────────────────────────────────────
    public function showUpload()
    {
        if (!session('pob_company_id') || !session('pob_date')) {
            return redirect()->route('form.index')
                ->withErrors(['Sesi habis. Silakan isi data POB terlebih dahulu.']);
        }

        $company = Company::find(session('pob_company_id'));

        if (!$company) {
            return redirect()->route('form.index')
                ->withErrors(['Perusahaan tidak ditemukan. Silakan isi ulang.']);
        }

        // Buat objek dummy untuk view (belum ada di DB)
        $entry = (object) [
            'total_pob'      => session('pob_total'),
            'total_manpower' => session('pob_total_manpower'),
            'date'           => session('pob_date'),
            'informed_by'    => session('pob_informed_by'),
            'contact_wa'     => session('pob_contact_wa'),
        ];

        return view('form.upload', compact('entry', 'company'));
    }

    // ─────────────────────────────────────────────
    // STEP 2: Proses upload Excel
    //         Jika valid → simpan POB entry + karyawan ke DB
    // ─────────────────────────────────────────────
    public function processUpload(Request $request)
    {
        if (!session('pob_company_id') || !session('pob_date')) {
            return redirect()->route('form.index')
                ->withErrors(['Sesi habis. Silakan isi ulang data POB.']);
        }

        $request->validate([
            'employee_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'employee_file.required' => 'File Excel karyawan wajib diupload.',
            'employee_file.mimes'    => 'Format file harus .xlsx, .xls, atau .csv.',
            'employee_file.max'      => 'Ukuran file maksimal 10MB.',
        ]);

        $companyId  = (int) session('pob_company_id');
        $date       = session('pob_date');
        $pobTotal   = (int) session('pob_total');
        $manpower   = (int) session('pob_total_manpower');
        $informedBy = session('pob_informed_by');
        $contactWa  = session('pob_contact_wa');
        $company    = Company::find($companyId);

        if (!$company) {
            return redirect()->route('form.index')
                ->withErrors(['Perusahaan tidak ditemukan. Silakan isi ulang.']);
        }

        // ── Baca file ──
        try {
            $rows = $this->readExcel($request->file('employee_file')->getPathname());
        } catch (\Exception $e) {
            return back()->withErrors(['employee_file' => 'Gagal membaca file: ' . $e->getMessage()]);
        }

        if (count($rows) <= 1) {
            return back()->withErrors(['employee_file' => 'File Excel kosong atau hanya berisi header.']);
        }

        $header = array_map('strtolower', array_map('trim', $rows[0]));
        $colMap = $this->detectColumns($header);

        if (!isset($colMap['name'])) {
            return back()->withErrors(['employee_file' =>
                'Kolom "Nama" tidak ditemukan. Pastikan header Excel menggunakan: Nama/Name, ID/MinePermit/KTP, Jabatan, Departemen, Tipe.'
            ]);
        }

        // ── Parse & validasi semua baris ──
        $parsed  = [];
        $errors  = [];
        $seenIds = [];

        foreach (array_slice($rows, 1) as $idx => $row) {
            $lineNum = $idx + 2;
            $name    = trim($row[$colMap['name']] ?? '');

            if ($name === '') continue; // skip baris benar-benar kosong

            $idRaw    = trim($row[$colMap['id_number'] ?? -1] ?? '');
            $idNumber = $idRaw !== '' ? $idRaw : null;
            $position = trim($row[$colMap['position']   ?? -1] ?? '') ?: null;
            $dept     = trim($row[$colMap['department']  ?? -1] ?? '') ?: null;
            $typeRaw  = strtolower(trim($row[$colMap['employee_type'] ?? -1] ?? ''));

            $idType  = 'minepermit';
            $empType = 'employee';

            if (in_array($typeRaw, ['visitor','tamu','guest'])) {
                $empType = 'visitor';
                $idType  = 'ktp';
            }
            if ($idNumber && preg_match('/^\d{16}$/', $idNumber)) {
                $idType = 'ktp';
            }

            $rowErrors = [];

            if (mb_strlen($name) < 2) {
                $rowErrors[] = "Nama terlalu pendek";
            }

            // ID opsional — jika diisi cek panjang dan duplikat
            if (!empty($idNumber)) {
                if (strlen($idNumber) < 3) {
                    $rowErrors[] = "Nomor ID terlalu pendek (minimal 3 karakter)";
                } else {
                    $idKey = strtoupper(trim($idNumber));
                    if (isset($seenIds[$idKey])) {
                        $rowErrors[] = "Nomor ID '{$idNumber}' duplikat dengan baris {$seenIds[$idKey]}";
                    } else {
                        $seenIds[$idKey] = $lineNum;
                    }
                }
            }

            if (!empty($rowErrors)) {
                $errors[] = "Baris {$lineNum} ({$name}): " . implode(', ', $rowErrors);
                continue;
            }

            $parsed[] = [
                'company_id'    => $companyId,
                'date'          => $date,
                'id_number'     => !empty($idNumber) ? strtoupper(trim($idNumber)) : 'N/A-'.$lineNum,
                'id_type'       => $idType,
                'name'          => $name,
                'position'      => $position,
                'department'    => $dept,
                'employee_type' => $empType,
            ];
        }

        // ── Tolak jika ada baris error ──
        if (count($errors) > 0) {
            return back()
                ->withErrors(['employee_file' => 'File ditolak. Perbaiki ' . count($errors) . ' kesalahan berikut lalu upload ulang.'])
                ->with('row_errors', $errors);
        }

        // ── Cek jumlah ──
        $uploadCount = count($parsed);

        if ($uploadCount !== $pobTotal) {
            $rawDataRows = count(array_filter(
                array_slice($rows, 1),
                fn($r) => !empty(trim(implode('', array_map('strval', $r))))
            ));

            return back()
                ->withErrors(['employee_file' =>
                    "Jumlah karyawan di file ({$uploadCount} orang) tidak sesuai dengan Total POB ({$pobTotal} orang)."
                ])
                ->with('pob_mismatch', [
                    'uploaded' => $uploadCount,
                    'expected' => $pobTotal,
                    'diff'     => $uploadCount - $pobTotal,
                    'raw_rows' => $rawDataRows,
                ]);
        }

        // ── SEMUA VALID — baru simpan ke database ──
        $entry = PobEntry::updateOrCreate(
            ['company_id' => $companyId, 'date' => $date],
            [
                'total_pob'      => $uploadCount,
                'total_manpower' => $manpower,
                'informed_by'    => $informedBy,
                'contact_wa'     => $contactWa,
            ]
        );

        // Hapus data karyawan lama untuk entry+tanggal ini, insert ulang
        PobEmployee::where('company_id', $companyId)
            ->whereDate('date', $date)
            ->delete();

        $now    = now();
        $chunks = array_chunk($parsed, 200);
        foreach ($chunks as $chunk) {
            $rows_insert = array_map(fn($d) => array_merge($d, [
                'pob_entry_id' => $entry->id,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]), $chunk);
            PobEmployee::insert($rows_insert);
        }

        $oldCount = PobEmployee::where('pob_entry_id', $entry->id)->count();

        // Clear session
        session()->forget([
            'pob_company_id','pob_date','pob_total',
            'pob_total_manpower','pob_informed_by','pob_contact_wa',
        ]);

        return redirect()->route('form.done')->with('upload_result', [
            'company'      => $company->name,
            'date'         => $date,
            'total_pob'    => $uploadCount,
            'total_mp'     => $manpower,
            'new'          => $uploadCount,
            'updated'      => 0,
            'removed'      => 0,
            'mismatch'     => false,
            'original_pob' => $pobTotal,
            'row_errors'   => [],
        ]);
    }

    // ─────────────────────────────────────────────
    // DONE PAGE
    // ─────────────────────────────────────────────
    public function done()
    {
        if (!session('upload_result')) {
            return redirect()->route('form.index');
        }
        return view('form.done', ['result' => session('upload_result')]);
    }

    // ─────────────────────────────────────────────
    // DOWNLOAD TEMPLATE EXCEL
    // ─────────────────────────────────────────────
    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template POB');

        $headers = ['ID / MinePermit / KTP', 'Nama Lengkap', 'Jabatan', 'Departemen', 'Tipe (employee/visitor)'];
        $cols    = ['A','B','C','D','E'];
        $widths  = [24, 30, 22, 22, 22];

        foreach ($headers as $i => $h) {
            $cell = $cols[$i].'1';
            $sheet->setCellValue($cell, $h);
            $sheet->getStyle($cell)->applyFromArray([
                'font'      => ['bold'=>true,'color'=>['rgb'=>'FFFFFF'],'size'=>11],
                'fill'      => ['fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'startColor'=>['rgb'=>'1a3c5e']],
                'alignment' => ['horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'vertical'  =>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,'color'=>['rgb'=>'ffffff']]],
            ]);
            $sheet->getColumnDimension($cols[$i])->setWidth($widths[$i]);
        }
        $sheet->getRowDimension(1)->setRowHeight(24);

        // Baris contoh
        $samples = [
            ['MP-2024-001234',   'Ahmad Fauzi Ramadhan', 'Operator Alat Berat',  'Mining Operations', 'employee'],
            ['MP-2024-005678',   'Budi Santoso Wijaya',  'Safety Officer',        'HSE',               'employee'],
            ['3212345678901234', 'Cindy Maharani',        'Auditor Eksternal',     'Eksternal',         'visitor'],
            ['MP-2024-009101',   'Dedi Kurniawan',        'Mekanik Senior',        'Maintenance',       'employee'],
            ['MP-2024-009202',   'Eko Prasetyo',          'Supervisor Tambang',    'Mining Operations', 'employee'],
        ];

        foreach ($samples as $r => $row) {
            $rowNum = $r + 2;
            foreach ($row as $c => $val) {
                $cell = $cols[$c].$rowNum;
                $sheet->setCellValue($cell, $val);
                $sheet->getStyle($cell)->applyFromArray([
                    'fill'      => ['fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor'=>['rgb'=>$r%2===0?'F8FAFC':'FFFFFF']],
                    'borders'   => ['allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,'color'=>['rgb'=>'E2E8F0']]],
                    'alignment' => ['vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                ]);
            }
            $sheet->getRowDimension($rowNum)->setRowHeight(20);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->stream(
            fn() => $writer->save('php://output'),
            200,
            [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="Template_POB_Karyawan.xlsx"',
                'Cache-Control'       => 'max-age=0',
            ]
        );
    }

    // ─────────────────────────────────────────────
    // DOWNLOAD PANDUAN PDF
    // ─────────────────────────────────────────────
    public function downloadGuide()
    {
        $path = public_path('panduan-pob.pdf');

        if (!file_exists($path)) {
            abort(404, 'File panduan belum tersedia. Hubungi administrator.');
        }

        return response()->download($path, 'Panduan_Pengisian_POB.pdf');
    }

    // ─────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────
    private function readExcel(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet       = $spreadsheet->getActiveSheet();
        $data        = $sheet->toArray(null, true, true, false);

        // Buang baris trailing yang kosong semua
        while (!empty($data) && empty(array_filter(array_map('strval', end($data))))) {
            array_pop($data);
        }

        return array_values($data);
    }

    private function detectColumns(array $header): array
    {
        $map      = [];
        $patterns = [
            'id_number'     => ['id','minepermit','mine permit','ktp','nik','no id','nomor','id number','permit'],
            'name'          => ['nama','name','nama karyawan','nama lengkap','full name','employee name'],
            'position'      => ['jabatan','position','posisi','job title','title','job'],
            'department'    => ['departemen','department','dept','divisi','division','bagian','unit'],
            'employee_type' => ['tipe','type','kategori','jenis','employee type','karyawan/visitor'],
        ];

        foreach ($patterns as $key => $needles) {
            foreach ($header as $idx => $col) {
                if (isset($map[$key])) break;
                foreach ($needles as $needle) {
                    if (str_contains($col, $needle)) {
                        $map[$key] = $idx;
                        break;
                    }
                }
            }
        }
        return $map;
    }
}