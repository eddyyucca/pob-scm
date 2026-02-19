<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PobEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $viewType = $request->get('view', 'daily');
        $date     = $request->get('date', now()->toDateString());
        $month    = $request->get('month', now()->format('Y-m'));
        $week     = $request->get('week', now()->format('o-\WW'));

        $weekParts = explode('-W', strtoupper($week));
        $year    = $weekParts[0] ?? now()->format('o');
        $weekNum = $weekParts[1] ?? now()->format('W');

        // =====================================================
        // DAILY — ambil entry terbaru per perusahaan per hari
        // =====================================================
        $dailyData = PobEntry::with('company')
            ->whereDate('date', $date)
            ->whereIn('id', function($q) {
                $q->selectRaw('MAX(id)')
                  ->from('pob_entries')
                  ->groupBy('company_id', DB::raw('DATE(date)'));
            })
            ->orderBy('total_pob', 'desc')
            ->get();

        $dailyTotals = [
            'pob'      => $dailyData->sum('total_pob'),
            'mp'       => $dailyData->sum('total_manpower'),
            'ratio'    => $dailyData->sum('total_manpower') > 0
                          ? round(($dailyData->sum('total_pob') / $dailyData->sum('total_manpower')) * 100, 1)
                          : 0,
            'reporters' => $dailyData->count(),
        ];

        // =====================================================
        // WEEKLY — SUM per perusahaan dalam 1 minggu
        // =====================================================
        try {
            $weekStart = date('Y-m-d', strtotime("{$year}-W{$weekNum}-1"));
        } catch (\Exception $e) {
            $weekStart = now()->startOfWeek()->toDateString();
        }

        $weeklyData = PobEntry::with('company')
            ->selectRaw('company_id, SUM(total_pob) as pob, SUM(total_manpower) as mp, COUNT(DISTINCT DATE(date)) as days')
            ->whereRaw('YEARWEEK(date, 1) = YEARWEEK(?, 1)', [$weekStart])
            ->whereIn('id', function($q) {
                $q->selectRaw('MAX(id)')
                  ->from('pob_entries')
                  ->groupBy('company_id', DB::raw('DATE(date)'));
            })
            ->groupBy('company_id')
            ->orderBy('pob', 'desc')
            ->get();

        $weeklyTotals = [
            'pob'   => $weeklyData->sum('pob'),
            'mp'    => $weeklyData->sum('mp'),
            'ratio' => $weeklyData->sum('mp') > 0
                       ? round(($weeklyData->sum('pob') / $weeklyData->sum('mp')) * 100, 1)
                       : 0,
            'reporters' => $weeklyData->count(),
        ];

        // =====================================================
        // MONTHLY
        // =====================================================
        [$mYear, $mMonth] = explode('-', $month . '-01');

        $monthlyData = PobEntry::with('company')
            ->selectRaw('company_id, SUM(total_pob) as pob, SUM(total_manpower) as mp, COUNT(DISTINCT DATE(date)) as days')
            ->whereYear('date', $mYear)
            ->whereMonth('date', $mMonth)
            ->whereIn('id', function($q) {
                $q->selectRaw('MAX(id)')
                  ->from('pob_entries')
                  ->groupBy('company_id', DB::raw('DATE(date)'));
            })
            ->groupBy('company_id')
            ->orderBy('pob', 'desc')
            ->get();

        $monthlyTotals = [
            'pob'   => $monthlyData->sum('pob'),
            'mp'    => $monthlyData->sum('mp'),
            'ratio' => $monthlyData->sum('mp') > 0
                       ? round(($monthlyData->sum('pob') / $monthlyData->sum('mp')) * 100, 1)
                       : 0,
            'reporters' => $monthlyData->count(),
        ];

        // =====================================================
        // CHART 1: Trend harian 30 hari terakhir
        // =====================================================
        $trendData = DB::select("
            SELECT
                DATE(date) as date,
                SUM(total_pob) as pob,
                SUM(total_manpower) as mp,
                COUNT(DISTINCT company_id) as reporters
            FROM pob_entries
            WHERE id IN (
                SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date)
            )
            AND date >= ?
            GROUP BY DATE(date)
            ORDER BY date ASC
        ", [now()->subDays(29)->toDateString()]);
        $trendData = collect($trendData);

        // =====================================================
        // CHART 2: Trend mingguan 12 minggu terakhir
        // =====================================================
        $weeklyTrend = DB::select("
            SELECT
                YEARWEEK(date, 1) as yw,
                MIN(DATE(date)) as week_start,
                SUM(total_pob) as pob,
                SUM(total_manpower) as mp
            FROM pob_entries
            WHERE id IN (
                SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date)
            )
            AND date >= ?
            GROUP BY YEARWEEK(date, 1)
            ORDER BY yw ASC
        ", [now()->subWeeks(11)->startOfWeek()->toDateString()]);
        $weeklyTrend = collect($weeklyTrend);

        // =====================================================
        // CHART 3: Trend bulanan 12 bulan terakhir
        // =====================================================
        $monthlyTrend = DB::select("
            SELECT
                DATE_FORMAT(date, '%Y-%m') as period,
                SUM(total_pob) as pob,
                SUM(total_manpower) as mp
            FROM pob_entries
            WHERE id IN (
                SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date)
            )
            AND date >= ?
            GROUP BY DATE_FORMAT(date, '%Y-%m')
            ORDER BY period ASC
        ", [now()->subMonths(11)->startOfMonth()->toDateString()]);
        $monthlyTrend = collect($monthlyTrend);

        // =====================================================
        // SUMMARY STATS
        // =====================================================
        $totalCompanies  = Company::where('is_active', true)->count();
        $totalEntries    = PobEntry::count();
        $latestDate      = PobEntry::max('date');
        $activeToday     = PobEntry::whereDate('date', $date)->distinct('company_id')->count();

        $companies = Company::where('is_active', true)->orderBy('name')->get();

        return view('dashboard.index', compact(
            'dailyData', 'dailyTotals',
            'weeklyData', 'weeklyTotals',
            'monthlyData', 'monthlyTotals',
            'trendData', 'weeklyTrend', 'monthlyTrend',
            'companies',
            'date', 'week', 'month', 'viewType',
            'totalCompanies', 'totalEntries', 'latestDate', 'activeToday'
        ));
    }

    public function export(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to   = $request->get('to', now()->toDateString());

        $entries = PobEntry::with('company')
            ->whereBetween('date', [$from, $to])
            ->whereIn('id', function($q) {
                $q->selectRaw('MAX(id)')
                  ->from('pob_entries')
                  ->groupBy('company_id', DB::raw('DATE(date)'));
            })
            ->orderBy('date')
            ->orderBy('company_id')
            ->get();

        $filename = "POB_Export_{$from}_{$to}.csv";
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($entries) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Tanggal', 'Perusahaan', 'Total POB', 'Total Manpower', 'Rasio (%)', 'Dilaporkan Oleh', 'Kontak WA']);
            foreach ($entries as $e) {
                $ratio = $e->total_manpower > 0 ? round(($e->total_pob / $e->total_manpower) * 100, 1) : 0;
                fputcsv($f, [
                    $e->date->format('Y-m-d'),
                    $e->company->name ?? '-',
                    $e->total_pob,
                    $e->total_manpower,
                    $ratio . '%',
                    $e->informed_by,
                    $e->contact_wa,
                ]);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }
}