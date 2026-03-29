<?php
namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PobEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
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

        // Data per perusahaan untuk minggu ini
        $weekData = DB::select("
            SELECT
                e.company_id,
                COUNT(DISTINCT DATE(e.date))   AS days_reported,
                SUM(e.total_pob)               AS total_pob,
                SUM(e.total_manpower)          AS total_mp,
                MAX(e.total_pob)               AS max_pob,
                MIN(e.total_pob)               AS min_pob,
                AVG(e.total_pob)               AS avg_pob,
                MAX(e.date)                    AS last_report
            FROM pob_entries e
            WHERE e.date BETWEEN ? AND ?
              AND e.id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))
            GROUP BY e.company_id
            ORDER BY total_pob DESC
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

        $prevData = DB::select("
            SELECT
                company_id,
                SUM(total_pob)      AS total_pob,
                SUM(total_manpower) AS total_mp,
                COUNT(DISTINCT DATE(date)) AS days_reported
            FROM pob_entries
            WHERE date BETWEEN ? AND ?
              AND id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))
            GROUP BY company_id
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
                'avg_pob'       => round($r->avg_pob, 1),
                'max_pob'       => (int)$r->max_pob,
                'min_pob'       => (int)$r->min_pob,
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
}