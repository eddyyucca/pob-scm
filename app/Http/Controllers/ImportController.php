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

        // Baca langsung dari temp file PHP - tidak perlu store ke disk
        $tmpPath = $request->file('excel_file')->getPathname();

        try {
            $spreadsheet = IOFactory::load($tmpPath);
            $sheet       = $spreadsheet->getActiveSheet();
            $rows        = $sheet->toArray(null, true, true, false);
        } catch (\Exception $e) {
            return back()->withErrors(['excel_file' => 'Gagal membaca file: ' . $e->getMessage()]);
        }

        $header = array_map('strtolower', array_map('trim', $rows[0] ?? []));
        $colMap = $this->detectColumns($header);

        if (!isset($colMap['company'], $colMap['date'])) {
            return back()->withErrors([
                'excel_file' => 'Kolom wajib tidak ditemukan. Pastikan ada kolom: "Select Company" dan "Pick a date".'
            ]);
        }

        $companyCache = Company::pluck('id', 'name')->toArray();

        $inserted  = 0;
        $updated   = 0;
        $newComp   = 0;
        $errorRows = [];

        foreach (array_slice($rows, 1) as $rowIndex => $row) {
            $lineNumber  = $rowIndex + 2;
            $companyName = trim($row[$colMap['company']] ?? '');
            $dateRaw     = $row[$colMap['date']] ?? null;
            $mp          = $row[$colMap['mp']          ?? -1] ?? null;
            $pob         = $row[$colMap['pob']         ?? -1] ?? null;
            $informedBy  = trim($row[$colMap['informed_by'] ?? -1] ?? '');
            $contact     = trim($row[$colMap['contact']     ?? -1] ?? '');
            $email       = trim($row[$colMap['email']       ?? -1] ?? '');
            $submitter   = trim($row[$colMap['name']        ?? -1] ?? '');

            if (empty($companyName) || empty($dateRaw)) continue;

            $date = $this->parseDate($dateRaw);
            if (!$date) {
                $errorRows[] = "Baris {$lineNumber}: tanggal tidak valid ({$dateRaw})";
                continue;
            }

            $mpVal  = is_numeric($mp)  ? (int)$mp  : 0;
            $pobVal = is_numeric($pob) ? (int)$pob : 0;

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

            $result = PobEntry::updateOrCreate(
                ['company_id' => $companyCache[$companyName], 'date' => $date],
                [
                    'total_pob'       => $pobVal,
                    'total_manpower'  => $mpVal,
                    'informed_by'     => $informedBy ?: null,
                    'contact_wa'      => $contact    ?: null,
                    'submitted_by'    => $submitter  ?: null,
                    'submitted_email' => ($email && $email !== 'anonymous') ? $email : null,
                ]
            );

            $result->wasRecentlyCreated ? $inserted++ : $updated++;
        }

        return redirect()->route('dashboard.import')->with('import_result', [
            'inserted'      => $inserted,
            'updated'       => $updated,
            'new_companies' => $newComp,
            'errors'        => $errorRows,
        ]);
    }

    private function detectColumns(array $header): array
    {
        $map      = [];
        $patterns = [
            'company'     => ['select company', 'company', 'perusahaan'],
            'date'        => ['pick a date', 'date', 'tanggal'],
            'mp'          => ['total manpower', 'manpower', 'mp'],
            'pob'         => ['total personal on board', 'total pob', 'pob', 'jumlah karyawan'],
            'informed_by' => ['informed by', 'nama lengkap'],
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

    private function parseDate($value): ?string
    {
        if (empty($value)) return null;

        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        foreach (['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d'] as $fmt) {
            $d = \DateTime::createFromFormat($fmt, trim($value));
            if ($d) return $d->format('Y-m-d');
        }

        try {
            return (new \DateTime($value))->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}