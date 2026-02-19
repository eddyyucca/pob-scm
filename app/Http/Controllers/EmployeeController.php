<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PobEmployee;
use App\Models\PobEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    // -------------------------------------------------------
    // LIST & SEARCH
    // -------------------------------------------------------
    public function index(Request $request)
    {
        $date      = $request->get('date', now()->toDateString());
        $companyId = $request->get('company_id');
        $search    = $request->get('search');
        $dept      = $request->get('department');
        $type      = $request->get('employee_type');

        $query = PobEmployee::with('company')
            ->whereDate('date', $date);

        if ($companyId) $query->where('company_id', $companyId);
        if ($search)    $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('id_number', 'like', "%{$search}%");
        });
        if ($dept) $query->where('department', 'like', "%{$dept}%");
        if ($type) $query->where('employee_type', $type);

        $employees = $query->orderBy('company_id')->orderBy('name')->paginate(50);

        // Summary stats
        $summary = PobEmployee::whereDate('date', $date)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN employee_type="employee" THEN 1 ELSE 0 END) as total_employee'),
                DB::raw('SUM(CASE WHEN employee_type="visitor" THEN 1 ELSE 0 END) as total_visitor'),
                DB::raw('COUNT(DISTINCT department) as total_dept'),
                DB::raw('COUNT(DISTINCT company_id) as total_company')
            )
            ->first();

        // Departments untuk filter
        $departments = PobEmployee::whereDate('date', $date)
            ->whereNotNull('department')
            ->distinct()->pluck('department')->sort()->values();

        $companies = Company::where('is_active', true)->orderBy('name')->get();

        return view('dashboard.employees', compact(
            'employees', 'summary', 'departments', 'companies',
            'date', 'companyId', 'search', 'dept', 'type'
        ));
    }

    // -------------------------------------------------------
    // UPLOAD EXCEL KARYAWAN
    // -------------------------------------------------------
    public function showUpload()
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get();

        // POB entries yang belum punya data karyawan
        $pendingEntries = PobEntry::with('company')
            ->whereDate('date', '>=', now()->subDays(30)->toDateString())
            ->whereNotIn('id', function($q) {
                $q->select('pob_entry_id')->from('pob_employees')->distinct();
            })
            ->orderBy('date', 'desc')
            ->get();

        return view('dashboard.employee_upload', compact('companies', 'pendingEntries'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
            'company_id' => 'required|exists:companies,id',
            'date'       => 'required|date',
        ]);

        $companyId = $request->company_id;
        $date      = $request->date;

        // Cari pob_entry terkait
        $pobEntry = PobEntry::where('company_id', $companyId)
            ->whereDate('date', $date)
            ->orderBy('id', 'desc')
            ->first();

        if (!$pobEntry) {
            return back()->withErrors(['excel_file' => "Tidak ada data POB untuk perusahaan dan tanggal ini. Pastikan laporan POB sudah dikirim terlebih dahulu."]);
        }

        $tmpPath = $request->file('excel_file')->getPathname();

        try {
            $spreadsheet = IOFactory::load($tmpPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows  = $sheet->toArray(null, true, true, false);
        } catch (\Exception $e) {
            return back()->withErrors(['excel_file' => 'Gagal membaca file: ' . $e->getMessage()]);
        }

        $header = array_map('strtolower', array_map('trim', $rows[0] ?? []));
        $colMap = $this->detectColumns($header);

        if (!isset($colMap['name'])) {
            return back()->withErrors(['excel_file' => 'Kolom "Nama" tidak ditemukan di file Excel.']);
        }

        $inserted  = 0;
        $skipped   = 0;
        $errorRows = [];

        // Hapus data lama untuk entry ini sebelum insert baru
        PobEmployee::where('pob_entry_id', $pobEntry->id)->delete();

        foreach (array_slice($rows, 1) as $idx => $row) {
            $name = trim($row[$colMap['name']] ?? '');
            if (empty($name)) { $skipped++; continue; }

            $idNumber = trim($row[$colMap['id_number'] ?? -1] ?? '');
            $idType   = 'minepermit';

            // Auto deteksi tipe ID: KTP biasanya 16 digit angka
            if (!empty($idNumber) && preg_match('/^\d{16}$/', $idNumber)) {
                $idType = 'ktp';
            }

            // Cek apakah dari request ada override id_type di kolom
            $idTypeRaw = strtolower(trim($row[$colMap['id_type'] ?? -1] ?? ''));
            if (in_array($idTypeRaw, ['ktp', 'visitor', 'tamu'])) {
                $idType = 'ktp';
            }

            $empType = 'employee';
            $empTypeRaw = strtolower(trim($row[$colMap['employee_type'] ?? -1] ?? ''));
            if (in_array($empTypeRaw, ['visitor', 'tamu', 'guest'])) {
                $empType = 'visitor';
                $idType  = 'ktp';
            }

            PobEmployee::create([
                'pob_entry_id'  => $pobEntry->id,
                'company_id'    => $companyId,
                'date'          => $date,
                'id_number'     => $idNumber ?: 'N/A',
                'id_type'       => $idType,
                'name'          => $name,
                'position'      => trim($row[$colMap['position']   ?? -1] ?? '') ?: null,
                'department'    => trim($row[$colMap['department']  ?? -1] ?? '') ?: null,
                'employee_type' => $empType,
            ]);
            $inserted++;
        }

        // Update total_pob di pob_entry jika berbeda
        if ($inserted !== $pobEntry->total_pob) {
            $pobEntry->update(['total_pob' => $inserted]);
        }

        return redirect()->route('employees.upload')
            ->with('upload_result', [
                'inserted' => $inserted,
                'skipped'  => $skipped,
                'company'  => $pobEntry->company->name,
                'date'     => $date,
                'pob_count' => $pobEntry->total_pob,
            ]);
    }

    // -------------------------------------------------------
    // EXPORT KARYAWAN
    // -------------------------------------------------------
    public function export(Request $request)
    {
        $date      = $request->get('date', now()->toDateString());
        $companyId = $request->get('company_id');

        $query = PobEmployee::with('company')
            ->whereDate('date', $date);
        if ($companyId) $query->where('company_id', $companyId);

        $employees = $query->orderBy('company_id')->orderBy('name')->get();

        $filename = "Karyawan_POB_{$date}.csv";
        return response()->stream(function () use ($employees) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Tanggal','Perusahaan','Tipe ID','No ID','Nama','Jabatan','Departemen','Tipe']);
            foreach ($employees as $e) {
                fputcsv($f, [
                    $e->date->format('Y-m-d'),
                    $e->company->name ?? '-',
                    strtoupper($e->id_type),
                    $e->id_number,
                    $e->name,
                    $e->position ?? '-',
                    $e->department ?? '-',
                    $e->employee_type === 'visitor' ? 'Visitor/Tamu' : 'Karyawan',
                ]);
            }
            fclose($f);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // -------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------
    private function detectColumns(array $header): array
    {
        $map = [];
        $patterns = [
            'id_number'     => ['id', 'minepermit', 'mine permit', 'ktp', 'nik', 'no id', 'nomor id', 'id number'],
            'id_type'       => ['tipe id', 'id type', 'jenis id', 'type'],
            'name'          => ['nama', 'name', 'nama karyawan', 'nama lengkap', 'full name'],
            'position'      => ['jabatan', 'position', 'posisi', 'job title', 'title'],
            'department'    => ['departemen', 'department', 'dept', 'divisi', 'division', 'bagian'],
            'employee_type' => ['tipe', 'type', 'kategori', 'karyawan/visitor', 'employee type'],
        ];

        foreach ($patterns as $key => $needles) {
            foreach ($header as $idx => $col) {
                foreach ($needles as $needle) {
                    if (str_contains($col, $needle)) {
                        if (!isset($map[$key])) $map[$key] = $idx;
                        break;
                    }
                }
            }
        }
        return $map;
    }
}
