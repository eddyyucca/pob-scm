<?php
namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PobEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReportController extends Controller
{
    private const COMPANY_ORDER = [
        'PT Sulawesi Cahaya Mineral',
        'PT Prima Utama Sultra',
        'PT Mulia Rentalindo Persada',
        'PT Jakarta Anugerah Mandiri',
        'All Sub-Contractors PT JAM',
        'PT Malachite International Mining',
        'PT Garda Utama Nasional',
        'PT Satria Jaya Sultra',
        'PT Intertek Utama Services',
        'PT Mitra Usaha Katiga',
        'PT Surveyor Carbon Consulting Indonesia',
        'PT Bagong Dekaka Makmur',
        'PT Serasi Auto Raya',
        'PT Transkon Jaya',
        'PT Putra Morowali Sejahtera',
        'PT ESG New Energy Material',
        'MCC',
        'PT Hillcon Jaya Sakti',
        'PT Teknologi Infrastruktur Indonesia',
        'PT Andalan Duta Eka Nusantara',
        'PT Merdeka Mining Services',
        'PT Uniteda Arkato',
        'PT Dahana',
        'PT Sumber Semeru Indonesia',
        'PT Indonesia Konawe Industri Park',
        'PT Huayue Nickel Cobalt',
        'PT Huayue Nickel Cobalt (SLNC Project)',
        'PT Petronesia Benimel',
        'PT Petronesia Benimel (Infrastructure)',
        'All Sub-Contractors PB',
        'PT Geo Gea Mineralindo',
        'PT Surya Pomala Utama',
        'PT Bahana Selaras Alam',
        'PT Bintang Mandiri Perkasa Drill',
        'PT Tectona Mitra Utama',
        'PT Karyaindo Ciptanusa',
        'CTCE Group',
        'PT Sinar Terang Mandiri',
        'PT Tiga Putra Bungoro',
        'PT Presisi Digital Moderen Teknologi',
        'PT Superkrane Mitra Utama',
        'PT Mitra Ateda Selaras',
        'PT Mitra Cuan Abadi',
        'PT Rajawali Emas Ancora Lestari',
        'All Sub-Contractors Real',
        'PT Samudera Mulia Abadi',
        'PT Lancarjaya Maju Abadi',
        'PT Inti Karya Pasifik',
        'PT Bumiindo Mulia Mandiri',
        'PT Citramegah Karunia Bersama',
        'PT Sucofindo',
    ];

    public function index(Request $request)
    {
        $view    = $request->get('view', 'weekly');   // weekly | monthly | yearly
        $refDate = $request->get('ref', now()->toDateString());

        return match($view) {
            'monthly' => $this->monthly($request),
            'yearly'  => $this->yearly($request),
            default   => $this->weekly($request),
        };
    }

    public function exportCsv(Request $request)
    {
        [$view, $startDate, $endDate, $label] = $this->resolveExportPeriod($request);
        $rows = $this->getCompanyLatestRows($startDate, $endDate);

        $filename = 'laporan_pob_mp_'.$view.'_'.$label.'_'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'company_id',
                'perusahaan',
                'total_pob',
                'total_manpower',
                'tanggal_laporan',
                'pelapor',
                'status',
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->company_id,
                    $row->perusahaan,
                    $row->total_pob,
                    $row->total_manpower,
                    $row->tanggal_laporan,
                    $row->pelapor,
                    $row->status,
                ]);
            }

            fclose($out);
        }, $filename, $headers);
    }

    // ── WEEKLY REPORT ─────────────────────────────────────
    private function weekly(Request $request)
    {
        // Minggu yang dipilih (default minggu ini)
        $weekInput = $request->get('week', now()->format('o-\WW'));
        [$yr, $wn] = explode('-W', strtoupper($weekInput));
        $wn = str_pad($wn, 2, '0', STR_PAD_LEFT);

        try {
            $weekStart = Carbon::now()->setISODate((int)$yr, (int)$wn)->startOfWeek(); // Senin
            $weekEnd   = $weekStart->copy()->endOfWeek();                               // Minggu
        } catch (\Exception $e) {
            $weekStart = Carbon::now()->startOfWeek();
            $weekEnd   = Carbon::now()->endOfWeek();
        }

        $minDays = 6; // minimal lapor Senin-Sabtu

        // Data per perusahaan untuk minggu ini — ambil data TERAKHIR saja per perusahaan
        $weekData = DB::select("
            SELECT
                w.company_id,
                w.days_reported,
                e.total_pob,
                e.total_manpower               AS total_mp,
                e.date                         AS last_report
            FROM (
                SELECT
                    company_id,
                    MAX(id)                        AS last_id,
                    COUNT(DISTINCT DATE(date))      AS days_reported
                FROM pob_entries
                WHERE date BETWEEN ? AND ?
                  AND id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))
                GROUP BY company_id
            ) AS w
            JOIN pob_entries e ON e.id = w.last_id
            ORDER BY e.total_pob DESC
        ", [$weekStart->toDateString(), $weekEnd->toDateString()]);
        $weekData = collect($weekData);

        // Data per hari dalam minggu ini (untuk tabel per hari)
        $dailyData = DB::select("
            SELECT
                DATE(date) AS day,
                SUM(total_pob) AS pob,
                SUM(total_manpower) AS mp,
                COUNT(DISTINCT company_id) AS reporters
            FROM pob_entries
            WHERE date BETWEEN ? AND ?
              AND id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))
            GROUP BY DATE(date)
            ORDER BY day ASC
        ", [$weekStart->toDateString(), $weekEnd->toDateString()]);
        $dailyData = collect($dailyData);

        // Minggu sebelumnya untuk perbandingan selisih
        $prevStart = $weekStart->copy()->subWeek();
        $prevEnd   = $weekEnd->copy()->subWeek();

        // Minggu sebelumnya — ambil data TERAKHIR saja per perusahaan
        $prevData = DB::select("
            SELECT
                w.company_id,
                e.total_pob,
                e.total_manpower               AS total_mp,
                w.days_reported
            FROM (
                SELECT
                    company_id,
                    MAX(id)                        AS last_id,
                    COUNT(DISTINCT DATE(date))      AS days_reported
                FROM pob_entries
                WHERE date BETWEEN ? AND ?
                  AND id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))
                GROUP BY company_id
            ) AS w
            JOIN pob_entries e ON e.id = w.last_id
        ", [$prevStart->toDateString(), $prevEnd->toDateString()]);
        $prevMap = collect($prevData)->keyBy('company_id');

        // Gabungkan dengan company name
        $companyMap = Company::where('is_active', true)->get()->keyBy('id');

        $rows = $weekData->map(function($r) use ($companyMap, $prevMap, $minDays) {
            $prev    = $prevMap->get($r->company_id);
            $company = $companyMap->get($r->company_id);

            $pobDiff = $prev ? ($r->total_pob - $prev->total_pob) : null;
            $mpDiff  = $prev ? ($r->total_mp  - $prev->total_mp)  : null;

            return (object)[
                'company_id'    => $r->company_id,
                'company_name'  => $company?->name ?? '-',
                'days_reported' => (int)$r->days_reported,
                'total_pob'     => (int)$r->total_pob,
                'total_mp'      => (int)$r->total_mp,
                'last_report'   => $r->last_report,
                'met_minimum'   => (int)$r->days_reported >= $minDays,
                'pob_diff'      => $pobDiff,
                'mp_diff'       => $mpDiff,
                'prev_pob'      => $prev ? (int)$prev->total_pob : null,
                'prev_mp'       => $prev ? (int)$prev->total_mp  : null,
            ];
        });

        // Perusahaan yang belum lapor sama sekali minggu ini
        $reportedIds = $weekData->pluck('company_id')->toArray();
        $notReported = Company::where('is_active', true)
            ->whereNotIn('id', $reportedIds)
            ->orderBy('name')->get();

        // Summary totals
        $totalPob      = $rows->sum('total_pob');
        $totalMp       = $rows->sum('total_mp');
        $totalPrevPob  = collect($prevData)->sum('total_pob');
        $totalPrevMp   = collect($prevData)->sum('total_mp');
        $metMinimum    = $rows->where('met_minimum', true)->count();
        $notMetMinimum = $rows->where('met_minimum', false)->count();

        // Sparkline 7 hari
        $days = collect(range(0,6))->map(fn($i) => $weekStart->copy()->addDays($i)->toDateString());

        $companies = Company::where('is_active', true)->orderBy('name')->get();

        return view('dashboard.report', compact(
            'rows', 'dailyData', 'notReported',
            'weekStart', 'weekEnd', 'prevStart', 'prevEnd',
            'totalPob', 'totalMp', 'totalPrevPob', 'totalPrevMp',
            'metMinimum', 'notMetMinimum',
            'days', 'weekInput', 'companies',
            'minDays',
        ) + ['view' => 'weekly']);
    }

    // ── MONTHLY REPORT ────────────────────────────────────
    private function monthly(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        [$yr, $mo] = explode('-', $month);

        $monthStart = Carbon::create($yr, $mo, 1)->startOfMonth();
        $monthEnd   = $monthStart->copy()->endOfMonth();

        // Per minggu dalam bulan
        $weeklyInMonth = DB::select("
            SELECT
                YEARWEEK(date, 1)              AS yw,
                MIN(DATE(date))                AS week_start,
                MAX(DATE(date))                AS week_end,
                SUM(total_pob)                 AS total_pob,
                SUM(total_manpower)            AS total_mp,
                COUNT(DISTINCT company_id)     AS reporters,
                COUNT(DISTINCT DATE(date))     AS days
            FROM pob_entries
            WHERE date BETWEEN ? AND ?
              AND id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))
            GROUP BY YEARWEEK(date, 1)
            ORDER BY yw ASC
        ", [$monthStart->toDateString(), $monthEnd->toDateString()]);
        $weeklyInMonth = collect($weeklyInMonth);

        // Per perusahaan dalam bulan
        $companyMonth = DB::select("
            SELECT
                company_id,
                COUNT(DISTINCT DATE(date))  AS days_reported,
                SUM(total_pob)              AS total_pob,
                SUM(total_manpower)         AS total_mp
            FROM pob_entries
            WHERE date BETWEEN ? AND ?
              AND id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))
            GROUP BY company_id
            ORDER BY total_pob DESC
        ", [$monthStart->toDateString(), $monthEnd->toDateString()]);
        $companyMonth = collect($companyMonth);

        // Bulan sebelumnya
        $prevMonthStart = $monthStart->copy()->subMonth()->startOfMonth();
        $prevMonthEnd   = $prevMonthStart->copy()->endOfMonth();

        $prevMonthData = DB::select("
            SELECT SUM(total_pob) AS total_pob, SUM(total_manpower) AS total_mp
            FROM pob_entries
            WHERE date BETWEEN ? AND ?
              AND id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))
        ", [$prevMonthStart->toDateString(), $prevMonthEnd->toDateString()]);

        $companyMap   = Company::where('is_active',true)->get()->keyBy('id');
        $monthRows    = $companyMonth->map(fn($r) => (object)[
            'company_name'  => $companyMap->get($r->company_id)?->name ?? '-',
            'days_reported' => (int)$r->days_reported,
            'total_pob'     => (int)$r->total_pob,
            'total_mp'      => (int)$r->total_mp,
        ]);

        $totalPob     = $companyMonth->sum('total_pob');
        $totalMp      = $companyMonth->sum('total_mp');
        $prevTotalPob = $prevMonthData[0]->total_pob ?? 0;
        $prevTotalMp  = $prevMonthData[0]->total_mp  ?? 0;
        $companies    = Company::where('is_active',true)->orderBy('name')->get();

        return view('dashboard.report', compact(
            'weeklyInMonth', 'monthRows',
            'monthStart', 'monthEnd',
            'totalPob', 'totalMp', 'prevTotalPob', 'prevTotalMp',
            'month', 'companies',
        ) + ['view' => 'monthly', 'rows' => $monthRows,
              'dailyData'=>collect(),'notReported'=>collect(),
              'weekStart'=>$monthStart,'weekEnd'=>$monthEnd,
              'metMinimum'=>0,'notMetMinimum'=>0,
              'days'=>collect(),'weekInput'=>'','minDays'=>6,
              'totalPrevPob'=>$prevTotalPob,'totalPrevMp'=>$prevTotalMp,
              'prevStart'=>$prevMonthStart,'prevEnd'=>$prevMonthEnd,
        ]);
    }

    // ── EXPORT EXCEL KARYAWAN LAST UPDATE ────────────────
    public function exportEmployees()
    {
        // Ambil entry terakhir per perusahaan (aktif) — lintas waktu
        $lastEntries = DB::select("
            SELECT
                c.id   AS company_id,
                c.name AS company_name,
                pe.id             AS entry_id,
                pe.date           AS entry_date,
                pe.total_pob      AS total_pob,
                pe.total_manpower AS total_manpower
            FROM companies c
            LEFT JOIN (
                SELECT company_id, MAX(id) AS last_id
                FROM pob_entries
                WHERE id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))
                GROUP BY company_id
            ) AS latest ON latest.company_id = c.id
            LEFT JOIN pob_entries pe ON pe.id = latest.last_id
            WHERE c.is_active = 1
            ORDER BY c.name
        ");

        $entryIds = collect($lastEntries)->whereNotNull('entry_id')->pluck('entry_id')->toArray();

        // Ambil semua karyawan untuk entry terakhir tersebut
        $empGroups = DB::table('pob_employees')
            ->whereIn('pob_entry_id', $entryIds)
            ->orderBy('pob_entry_id')
            ->orderBy('name')
            ->get()
            ->groupBy('pob_entry_id');

        // Buat spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('POB Karyawan Last Update');

        $headers = ['No', 'Perusahaan', 'Tgl Laporan', 'Total POB', 'Total MP',
                    'Tipe ID', 'No ID', 'Nama', 'Jabatan', 'Departemen', 'Tipe'];
        $cols    = ['A','B','C','D','E','F','G','H','I','J','K'];

        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i].'1', $h);
        }

        // Style header
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(20);

        $row = 2;
        $no  = 1;

        foreach ($lastEntries as $entry) {
            $emps = $entry->entry_id ? ($empGroups->get($entry->entry_id) ?? collect()) : collect();

            if ($emps->isEmpty()) {
                $sheet->setCellValue('A'.$row, $no++);
                $sheet->setCellValue('B'.$row, $entry->company_name);
                $sheet->setCellValue('C'.$row, $entry->entry_date ?? 'Belum ada data');
                $sheet->setCellValue('D'.$row, $entry->total_pob    ?? '-');
                $sheet->setCellValue('E'.$row, $entry->total_manpower ?? '-');
                $row++;
            } else {
                foreach ($emps as $emp) {
                    $sheet->setCellValue('A'.$row, $no++);
                    $sheet->setCellValue('B'.$row, $entry->company_name);
                    $sheet->setCellValue('C'.$row, $entry->entry_date);
                    $sheet->setCellValue('D'.$row, (int)$entry->total_pob);
                    $sheet->setCellValue('E'.$row, (int)$entry->total_manpower);
                    $sheet->setCellValue('F'.$row, $emp->id_type);
                    $sheet->setCellValue('G'.$row, $emp->id_number);
                    $sheet->setCellValue('H'.$row, $emp->name);
                    $sheet->setCellValue('I'.$row, $emp->position);
                    $sheet->setCellValue('J'.$row, $emp->department);
                    $sheet->setCellValue('K'.$row, $emp->employee_type);
                    $row++;
                }
            }
        }

        // Zebra striping & border per baris
        for ($r = 2; $r < $row; $r++) {
            $style = $sheet->getStyle('A'.$r.':K'.$r);
            if ($r % 2 === 0) {
                $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
            }
            $style->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setRGB('E2E8F0');
        }

        // Auto-size kolom
        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Freeze header row
        $sheet->freezePane('A2');

        $filename = 'POB_Karyawan_LastUpdate_'.now()->format('Ymd_His').'.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'       => 'max-age=0',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    // ── YEARLY REPORT ─────────────────────────────────────
    private function yearly(Request $request)
    {
        $year = (int) $request->get('year', now()->year);

        // Per bulan dalam tahun
        $monthlyData = DB::select("
            SELECT
                DATE_FORMAT(date, '%Y-%m')      AS period,
                DATE_FORMAT(date, '%Y-%m-01')   AS period_start,
                SUM(total_pob)                  AS total_pob,
                SUM(total_manpower)             AS total_mp,
                COUNT(DISTINCT company_id)      AS reporters,
                COUNT(DISTINCT DATE(date))      AS days
            FROM pob_entries
            WHERE YEAR(date) = ?
              AND id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))
            GROUP BY DATE_FORMAT(date, '%Y-%m'), DATE_FORMAT(date, '%Y-%m-01')
            ORDER BY period ASC
        ", [$year]);
        $monthlyData = collect($monthlyData);

        // Tahun sebelumnya untuk perbandingan
        $prevYearData = DB::select("
            SELECT
                DATE_FORMAT(date, '%m') AS mo,
                SUM(total_pob)          AS total_pob,
                SUM(total_manpower)     AS total_mp
            FROM pob_entries
            WHERE YEAR(date) = ?
              AND id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))
            GROUP BY DATE_FORMAT(date, '%m')
            ORDER BY mo ASC
        ", [$year - 1]);
        $prevYearMap = collect($prevYearData)->keyBy('mo');

        $monthlyRows = $monthlyData->map(fn($r) => (object)[
            'period'     => $r->period,
            'label'      => Carbon::parse($r->period_start)->locale('id')->isoFormat('MMMM'),
            'total_pob'  => (int)$r->total_pob,
            'total_mp'   => (int)$r->total_mp,
            'reporters'  => (int)$r->reporters,
            'days'       => (int)$r->days,
            'prev_pob'   => (int)($prevYearMap->get(Carbon::parse($r->period_start)->format('m'))?->total_pob ?? 0),
            'prev_mp'    => (int)($prevYearMap->get(Carbon::parse($r->period_start)->format('m'))?->total_mp ?? 0),
        ]);

        $totalPob     = $monthlyData->sum('total_pob');
        $totalMp      = $monthlyData->sum('total_mp');
        $prevTotalPob = collect($prevYearData)->sum('total_pob');
        $prevTotalMp  = collect($prevYearData)->sum('total_mp');
        $companies    = Company::where('is_active',true)->orderBy('name')->get();

        return view('dashboard.report', compact(
            'monthlyRows', 'year',
            'totalPob', 'totalMp', 'prevTotalPob', 'prevTotalMp',
            'companies',
        ) + ['view'=>'yearly','rows'=>$monthlyRows,
              'dailyData'=>collect(),'notReported'=>collect(),
              'weekStart'=>Carbon::create($year,1,1),'weekEnd'=>Carbon::create($year,12,31),
              'metMinimum'=>0,'notMetMinimum'=>0,
              'days'=>collect(),'weekInput'=>'','minDays'=>6,
              'totalPrevPob'=>$prevTotalPob,'totalPrevMp'=>$prevTotalMp,
              'prevStart'=>Carbon::create($year-1,1,1),'prevEnd'=>Carbon::create($year-1,12,31),
              'month'=>$year.'-01','weeklyInMonth'=>collect(),'monthRows'=>collect(),
              'monthStart'=>Carbon::create($year,1,1),'monthEnd'=>Carbon::create($year,12,31),
        ]);
    }

    private function getCompanyLatestRows(string $startDate, string $endDate)
    {
        $fieldList = implode("', '", array_map(fn($name) => str_replace("'", "\\'", $name), self::COMPANY_ORDER));

        return collect(DB::select("
            SELECT
                c.id AS company_id,
                c.name AS perusahaan,
                COALESCE(e.total_pob, 0) AS total_pob,
                COALESCE(e.total_manpower, 0) AS total_manpower,
                e.date AS tanggal_laporan,
                e.informed_by AS pelapor,
                CASE
                    WHEN e.id IS NULL THEN 'Belum Lapor'
                    ELSE 'Sudah Lapor'
                END AS status
            FROM companies c
            LEFT JOIN (
                SELECT
                    x.company_id,
                    MAX(x.id) AS last_id
                FROM pob_entries x
                WHERE x.date BETWEEN ? AND ?
                  AND x.id IN (
                      SELECT MAX(p.id)
                      FROM pob_entries p
                      GROUP BY p.company_id, DATE(p.date)
                  )
                GROUP BY x.company_id
            ) latest ON latest.company_id = c.id
            LEFT JOIN pob_entries e ON e.id = latest.last_id
            WHERE c.is_active = 1
            ORDER BY FIELD(c.name, '{$fieldList}'), c.name
        ", [$startDate, $endDate]));
    }

    private function resolveExportPeriod(Request $request): array
    {
        $view = $request->get('view', 'weekly');

        return match ($view) {
            'monthly' => $this->resolveMonthlyExportPeriod($request),
            'yearly' => $this->resolveYearlyExportPeriod($request),
            default => $this->resolveWeeklyExportPeriod($request),
        };
    }

    private function resolveWeeklyExportPeriod(Request $request): array
    {
        $weekInput = $request->get('week', now()->format('o-\WW'));
        [$yr, $wn] = explode('-W', strtoupper($weekInput));
        $wn = str_pad($wn, 2, '0', STR_PAD_LEFT);

        try {
            $start = Carbon::now()->setISODate((int) $yr, (int) $wn)->startOfWeek();
            $end = $start->copy()->endOfWeek();
        } catch (\Exception $e) {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
        }

        return ['weekly', $start->toDateString(), $end->toDateString(), $start->format('Ymd').'_'.$end->format('Ymd')];
    }

    private function resolveMonthlyExportPeriod(Request $request): array
    {
        $month = $request->get('month', now()->format('Y-m'));
        [$yr, $mo] = explode('-', $month);

        $start = Carbon::create($yr, $mo, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return ['monthly', $start->toDateString(), $end->toDateString(), $start->format('Ym')];
    }

    private function resolveYearlyExportPeriod(Request $request): array
    {
        $year = (int) $request->get('year', now()->year);
        $start = Carbon::create($year, 1, 1)->startOfYear();
        $end = $start->copy()->endOfYear();

        return ['yearly', $start->toDateString(), $end->toDateString(), (string) $year];
    }
}
