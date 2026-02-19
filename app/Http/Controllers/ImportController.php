<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PobEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    public function showForm()
    {
        return view('dashboard.import');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ], [
            'excel_file.required' => 'Pilih file Excel terlebih dahulu.',
            'excel_file.mimes'    => 'Format file harus .xlsx, .xls, atau .csv.',
            'excel_file.max'      => 'Ukuran file maksimal 20MB.',
        ]);

        $path = $request->file('excel_file')->store('imports', 'local');
        $fullPath = storage_path('app/' . $path);

        try {
            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows  = $sheet->toArray(null, true, true, false);
        } catch (\Exception $e) {
            return back()->withErrors(['excel_file' => 'Gagal membaca file: ' . $e->getMessage()]);
        }

        // Deteksi header di baris pertama
        $header = array_map('strtolower', array_map('trim', $rows[0] ?? []));

        // Mapping kolom — cari index kolom berdasarkan nama
        $colMap = $this->detectColumns($header);

        if (!isset($colMap['company'], $colMap['date'])) {
            return back()->withErrors([
                'excel_file' => 'Kolom wajib tidak ditemukan. Pastikan ada kolom: "Select Company" dan "Pick a date".'
            ]);
        }

        // Cache companies agar tidak query per baris
        $companyCache = Company::pluck('id', 'name')->toArray();

        $inserted  = 0;
        $skipped   = 0;
        $newComp   = 0;
        $errorRows = [];

        // Proses per baris mulai baris ke-2
        foreach (array_slice($rows, 1) as $rowIndex => $row) {
            $lineNumber = $rowIndex + 2;

            $companyName = trim($row[$colMap['company']] ?? '');
            $dateRaw     = $row[$colMap['date']] ?? null;
            $mp          = $row[$colMap['mp']   ?? -1] ?? null;
            $pob         = $row[$colMap['pob']  ?? -1] ?? null;
            $informedBy  = trim($row[$colMap['informed_by'] ?? -1] ?? '');
            $contact     = trim($row[$colMap['contact']     ?? -1] ?? '');
            $email       = trim($row[$colMap['email']       ?? -1] ?? '');
            $submitter   = trim($row[$colMap['name']        ?? -1] ?? '');

            // Skip baris kosong
            if (empty($companyName) || empty($dateRaw)) {
                $skipped++;
                continue;
            }

            // Parse tanggal
            $date = $this->parseDate($dateRaw);
            if (!$date) {
                $errorRows[] = "Baris {$lineNumber}: format tanggal tidak valid ({$dateRaw})";
                $skipped++;
                continue;
            }

            // Cast angka
            $mpVal  = is_numeric($mp)  ? (int)$mp  : 0;
            $pobVal = is_numeric($pob) ? (int)$pob : 0;

            // Auto-create company jika belum ada
            if (!isset($companyCache[$companyName])) {
                $company = Company::create([
                    'name'      => $companyName,
                    'slug'      => Str::slug($companyName),
                    'type'      => 'contractor',
                    'is_active' => true,
                ]);
                $companyCache[$companyName] = $company->id;
                $newComp++;
            }

            $companyId = $companyCache[$companyName];

            // Upsert: update jika sudah ada, insert jika belum
            $result = PobEntry::updateOrCreate(
                ['company_id' => $companyId, 'date' => $date],
                [
                    'total_pob'        => $pobVal,
                    'total_manpower'   => $mpVal,
                    'informed_by'      => $informedBy ?: null,
                    'contact_wa'       => $contact    ?: null,
                    'submitted_by'     => $submitter  ?: null,
                    'submitted_email'  => ($email && $email !== 'anonymous') ? $email : null,
                ]
            );

            $result->wasRecentlyCreated ? $inserted++ : $skipped++;
        }

        // Hapus file temp
        \Storage::disk('local')->delete($path);

        $summary = [
            'inserted' => $inserted,
            'updated'  => $skipped,
            'new_companies' => $newComp,
            'errors'   => $errorRows,
        ];

        return redirect()->route('dashboard.import')
            ->with('import_result', $summary);
    }

    // -----------------------------------------------
    // Deteksi posisi kolom dari header
    // -----------------------------------------------
    private function detectColumns(array $header): array
    {
        $map = [];

        $patterns = [
            'company'     => ['select company', 'company', 'perusahaan'],
            'date'        => ['pick a date', 'date', 'tanggal'],
            'mp'          => ['total manpower', 'manpower', 'mp'],
            'pob'         => ['total personal on board', 'total pob', 'pob', 'jumlah karyawan'],
            'informed_by' => ['informed by', 'nama lengkap', 'informant'],
            'contact'     => ['contact whatsapp', 'whatsapp', 'contact', 'kontak'],
            'email'       => ['email'],
            'name'        => ['name', 'nama'],
        ];

        foreach ($patterns as $key => $needles) {
            foreach ($header as $idx => $col) {
                foreach ($needles as $needle) {
                    if (str_contains($col, $needle)) {
                        $map[$key] = $idx;
                        break 2;
                    }
                }
            }
        }

        return $map;
    }

    // -----------------------------------------------
    // Parse berbagai format tanggal
    // -----------------------------------------------
    private function parseDate($value): ?string
    {
        if (empty($value)) return null;

        // Numeric = Excel serial date
        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d');
        }

        // String
        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d'];
        foreach ($formats as $fmt) {
            $d = \DateTime::createFromFormat($fmt, trim($value));
            if ($d) return $d->format('Y-m-d');
        }

        // Last resort
        try {
            return (new \DateTime($value))->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
