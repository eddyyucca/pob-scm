<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PobEmployee;
use App\Models\PobEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
    // STEP 1: Simpan data POB → redirect ke upload
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
            'total_pob.min'       => 'Total POB minimal 1 orang.',
            'total_manpower.min'  => 'Total Manpower minimal 1 orang.',
            'total_manpower.gte'  => 'Total Manpower tidak boleh kurang dari Total POB.',
        ]);

        $entry = PobEntry::updateOrCreate(
            ['company_id' => $validated['company_id'], 'date' => $validated['date']],
            $validated
        );

        // Simpan entry ID ke session untuk dipakai di step 2
        session([
            'pob_entry_id'   => $entry->id,
            'pob_company_id' => $validated['company_id'],
            'pob_date'       => $validated['date'],
            'pob_total'      => $validated['total_pob'],
        ]);

        return redirect()->route('form.upload')
            ->with('step1_success', true);
    }

    // ─────────────────────────────────────────────
    // STEP 2: Tampilkan halaman upload Excel
    // ─────────────────────────────────────────────
    public function showUpload()
    {
        if (!session('pob_entry_id')) {
            return redirect()->route('form.index')
                ->withErrors(['Sesi habis. Silakan isi data POB terlebih dahulu.']);
        }

        $entry   = PobEntry::with('company')->find(session('pob_entry_id'));
        $company = Company::find(session('pob_company_id'));

        return view('form.upload', compact('entry', 'company'));
    }

    // ─────────────────────────────────────────────
    // STEP 2: Proses upload Excel karyawan
    // ─────────────────────────────────────────────
    public function processUpload(Request $request)
    {
        if (!session('pob_entry_id')) {
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

        $entryId   = session('pob_entry_id');
        $companyId = session('pob_company_id');
        $date      = session('pob_date');
        $pobTotal  = session('pob_total');
        $entry     = PobEntry::with('company')->find($entryId);

        // Baca file
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

        // ── PARSE & VALIDASI semua baris dulu ──
        $parsed   = [];
        $errors   = [];
        $seenIds  = []; // deteksi duplikat ID dalam file

        foreach (array_slice($rows, 1) as $idx => $row) {
            $lineNum = $idx + 2;
            $name    = trim($row[$colMap['name']] ?? '');

            if (empty($name)) continue; // skip baris kosong

            $idRaw    = trim($row[$colMap['id_number'] ?? -1] ?? '');
            $idNumber = $idRaw !== '' ? $idRaw : null;
            $position = trim($row[$colMap['position']   ?? -1] ?? '') ?: null;
            $dept     = trim($row[$colMap['department']  ?? -1] ?? '') ?: null;

            // Deteksi tipe ID
            $idType   = 'minepermit';
            $empType  = 'employee';
            $typeRaw  = strtolower(trim($row[$colMap['employee_type'] ?? -1] ?? ''));

            if (in_array($typeRaw, ['visitor','tamu','guest'])) {
                $empType = 'visitor';
                $idType  = 'ktp';
            }
            if ($idNumber && preg_match('/^\d{16}$/', $idNumber)) {
                $idType = 'ktp';
            }

            // ── VALIDASI ──
            $rowErrors = [];

            if (mb_strlen($name) < 2) {
                $rowErrors[] = "Nama terlalu pendek";
            }

            if ($idNumber && strlen($idNumber) < 3) {
                $rowErrors[] = "Nomor ID terlalu pendek";
            }

            // Duplikat ID dalam file yang sama
            if ($idNumber) {
                $idKey = strtoupper($idNumber);
                if (isset($seenIds[$idKey])) {
                    $rowErrors[] = "ID '{$idNumber}' duplikat dengan baris {$seenIds[$idKey]}";
                } else {
                    $seenIds[$idKey] = $lineNum;
                }
            }

            if (!empty($rowErrors)) {
                $errors[] = "Baris {$lineNum} ({$name}): " . implode(', ', $rowErrors);
                continue;
            }

            $parsed[] = [
                'pob_entry_id'  => $entryId,
                'company_id'    => $companyId,
                'date'          => $date,
                'id_number'     => $idNumber ?? 'N/A',
                'id_type'       => $idType,
                'name'          => $name,
                'position'      => $position,
                'department'    => $dept,
                'employee_type' => $empType,
            ];
        }

        // Jika terlalu banyak error, stop
        if (count($errors) > 0 && count($parsed) === 0) {
            return back()->withErrors([
                'employee_file' => 'Semua baris gagal divalidasi. Periksa kembali file Excel Anda.',
                'row_errors'    => $errors,
            ])->with('row_errors', $errors);
        }

        // ── CEK JUMLAH vs POB yang dilaporkan ──
        $uploadCount = count($parsed);
        $mismatch    = false;
        if ($uploadCount !== $pobTotal) {
            $mismatch = true;
            $entry->update(['total_pob' => $uploadCount]);
        }

        // ── DELETE-THEN-INSERT per (company_id + date) ──
        // Upload ulang hari yang sama = replace total
        // Histori hari lain tetap aman karena filter by date
        $oldCount = PobEmployee::where('company_id', $companyId)
            ->whereDate('date', $date)
            ->count();

        PobEmployee::where('company_id', $companyId)
            ->whereDate('date', $date)
            ->delete();

        // Insert semua sekaligus per 200 baris agar tidak timeout
        $now    = now();
        $chunks = array_chunk($parsed, 200);
        foreach ($chunks as $chunk) {
            $insertRows = array_map(fn($d) => array_merge($d, [
                'created_at' => $now,
                'updated_at' => $now,
            ]), $chunk);
            PobEmployee::insert($insertRows);
        }

        $newCount     = max(0, $uploadCount - $oldCount);
        $updated      = min($oldCount, $uploadCount);
        $removedCount = max(0, $oldCount - $uploadCount);

        // Clear session
        session()->forget(['pob_entry_id', 'pob_company_id', 'pob_date', 'pob_total']);

        return redirect()->route('form.done')->with('upload_result', [
            'company'       => $entry->company->name,
            'date'          => $date,
            'total_pob'     => $uploadCount,
            'total_mp'      => $entry->fresh()->total_manpower,
            'new'           => $newCount,
            'updated'       => $updated,
            'removed'       => $removedCount,
            'mismatch'      => $mismatch,
            'original_pob'  => $pobTotal,
            'row_errors'    => $errors,
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
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template POB');

        $headers = ['ID / MinePermit / KTP', 'Nama Lengkap', 'Jabatan', 'Departemen', 'Tipe (employee/visitor)'];
        $cols    = ['A','B','C','D','E'];
        $widths  = [24, 30, 22, 22, 22];

        // Style header
        foreach ($headers as $i => $h) {
            $cell = $cols[$i].'1';
            $sheet->setCellValue($cell, $h);
            $sheet->getStyle($cell)->applyFromArray([
                'font' => ['bold'=>true, 'color'=>['rgb'=>'FFFFFF'], 'size'=>11],
                'fill' => ['fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor'=>['rgb'=>'1a3c5e']],
                'alignment' => ['horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color'=>['rgb'=>'ffffff']]],
            ]);
            $sheet->getColumnDimension($cols[$i])->setWidth($widths[$i]);
        }
        $sheet->getRowDimension(1)->setRowHeight(24);

        // Data contoh
        $samples = [
            ['MP-2024-001234', 'Ahmad Fauzi Ramadhan',   'Operator Alat Berat', 'Mining Operations', 'employee'],
            ['MP-2024-005678', 'Budi Santoso Wijaya',    'Safety Officer',       'HSE',               'employee'],
            ['3212345678901234','Cindy Maharani',         'Auditor Eksternal',    'Eksternal',         'visitor'],
            ['MP-2024-009101', 'Dedi Kurniawan',          'Mekanik Senior',       'Maintenance',       'employee'],
            ['MP-2024-009202', 'Eko Prasetyo',            'Supervisor Tambang',   'Mining Operations', 'employee'],
        ];

        $rowColors = ['F8FAFC', 'FFFFFF'];
        foreach ($samples as $r => $row) {
            $rowNum = $r + 2;
            foreach ($row as $c => $val) {
                $cell = $cols[$c].$rowNum;
                $sheet->setCellValue($cell, $val);
                $sheet->getStyle($cell)->applyFromArray([
                    'fill' => ['fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor'=>['rgb'=>$rowColors[$r%2]]],
                    'borders' => ['allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color'=>['rgb'=>'E2E8F0']]],
                    'alignment' => ['vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                ]);
            }
            $sheet->getRowDimension($rowNum)->setRowHeight(20);
        }

        // Catatan
        $noteRow = count($samples) + 3;
        $notes = [
            ['A'.$noteRow,     '⚠ PANDUAN PENGISIAN:', '1E293B', true],
            ['A'.($noteRow+1), '• Kolom Nama Lengkap wajib diisi, tidak boleh kosong.', '475569', false],
            ['A'.($noteRow+2), '• ID/MinePermit: isi nomor permit (contoh: MP-2024-XXXXXX). Untuk visitor, isi nomor KTP (16 digit angka).', '475569', false],
            ['A'.($noteRow+3), '• Jabatan & Departemen: opsional tapi sangat direkomendasikan untuk laporan yang lengkap.', '475569', false],
            ['A'.($noteRow+4), '• Tipe: isi "employee" untuk karyawan tetap/kontrak, "visitor" untuk tamu/auditor/non-karyawan.', '475569', false],
            ['A'.($noteRow+5), '• Hapus baris contoh di atas sebelum diupload. Baris header (baris 1) JANGAN dihapus.', 'DC2626', false],
            ['A'.($noteRow+6), '• Jika karyawan sudah ada di database, data akan diperbarui (bukan duplikat).', '475569', false],
            ['A'.($noteRow+7), '• Jumlah baris karyawan akan menentukan Total POB final.', '475569', false],
        ];

        foreach ($notes as [$cell, $text, $color, $bold]) {
            $sheet->setCellValue($cell, $text);
            $sheet->mergeCells($cell.':'.$cols[4].substr($cell,1));
            $sheet->getStyle($cell)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF'.$color))->setBold($bold);
            $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
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
    // HELPERS
    // ─────────────────────────────────────────────
    private function readExcel(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
    }

    private function detectColumns(array $header): array
    {
        $map = [];
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
