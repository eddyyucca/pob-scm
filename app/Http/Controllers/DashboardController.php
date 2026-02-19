<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PobEmployee;
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
        $weekNum = str_pad($weekParts[1] ?? now()->format('W'), 2, '0', STR_PAD_LEFT);

        // Subquery: ambil hanya entry terbaru per company per hari
        $latest = "SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date)";

        // ── DAILY ──
        $dailyData = PobEntry::with('company')
            ->whereDate('date', $date)
            ->whereRaw("id IN ($latest)")
            ->orderBy('total_pob', 'desc')
            ->get();

        $dPob = $dailyData->sum('total_pob');
        $dMp  = $dailyData->sum('total_manpower');
        $dailyTotals = ['pob'=>$dPob,'mp'=>$dMp,'ratio'=>$dMp>0?round($dPob/$dMp*100,1):0,'reporters'=>$dailyData->count()];

        // ── WEEKLY ──
        try { $ws = date('Y-m-d', strtotime("{$year}-W{$weekNum}-1")); }
        catch (\Exception $e) { $ws = now()->startOfWeek()->toDateString(); }

        $weeklyData = PobEntry::with('company')
            ->selectRaw('company_id, SUM(total_pob) as pob, SUM(total_manpower) as mp, COUNT(DISTINCT DATE(date)) as days')
            ->whereRaw('YEARWEEK(date,1)=YEARWEEK(?,1)',[$ws])
            ->whereRaw("id IN ($latest)")
            ->groupBy('company_id')->orderBy('pob','desc')->get();

        $wPob=$weeklyData->sum('pob'); $wMp=$weeklyData->sum('mp');
        $weeklyTotals=['pob'=>$wPob,'mp'=>$wMp,'ratio'=>$wMp>0?round($wPob/$wMp*100,1):0,'reporters'=>$weeklyData->count()];

        // ── MONTHLY ──
        [$mY,$mM] = array_pad(explode('-',$month),2,'01');
        $monthlyData = PobEntry::with('company')
            ->selectRaw('company_id, SUM(total_pob) as pob, SUM(total_manpower) as mp, COUNT(DISTINCT DATE(date)) as days')
            ->whereYear('date',$mY)->whereMonth('date',$mM)
            ->whereRaw("id IN ($latest)")
            ->groupBy('company_id')->orderBy('pob','desc')->get();

        $moPob=$monthlyData->sum('pob'); $moMp=$monthlyData->sum('mp');
        $monthlyTotals=['pob'=>$moPob,'mp'=>$moMp,'ratio'=>$moMp>0?round($moPob/$moMp*100,1):0,'reporters'=>$monthlyData->count()];

        // ── TREND 30 HARI ──
        $trendData = collect(DB::select("
            SELECT DATE(date) as date, SUM(total_pob) as pob, SUM(total_manpower) as mp,
                   COUNT(DISTINCT company_id) as reporters
            FROM pob_entries WHERE id IN ($latest) AND date>=?
            GROUP BY DATE(date) ORDER BY date ASC
        ", [now()->subDays(29)->toDateString()]));

        // ── TREND 12 MINGGU ──
        $weeklyTrend = collect(DB::select("
            SELECT YEARWEEK(date,1) as yw, MIN(DATE(date)) as week_start,
                   SUM(total_pob) as pob, SUM(total_manpower) as mp
            FROM pob_entries WHERE id IN ($latest) AND date>=?
            GROUP BY YEARWEEK(date,1) ORDER BY yw ASC
        ", [now()->subWeeks(11)->startOfWeek()->toDateString()]));

        // ── TREND 12 BULAN ──
        $monthlyTrend = collect(DB::select("
            SELECT DATE_FORMAT(date,'%Y-%m') as period,
                   SUM(total_pob) as pob, SUM(total_manpower) as mp
            FROM pob_entries WHERE id IN ($latest) AND date>=?
            GROUP BY DATE_FORMAT(date,'%Y-%m') ORDER BY period ASC
        ", [now()->subMonths(11)->startOfMonth()->toDateString()]));

        // ── EMPLOYEE STATS ──
        $empToday = PobEmployee::whereDate('date', $date)->count();

        $deptBreakdown = PobEmployee::whereDate('date', $date)
            ->whereNotNull('department')
            ->select('department', DB::raw('COUNT(*) as total'))
            ->groupBy('department')
            ->orderBy('total','desc')
            ->take(10)->get();

        // Per-entry: berapa yang sudah punya data karyawan
        $withEmp    = PobEntry::whereDate('date',$date)->whereRaw("id IN ($latest)")->whereHas('employees')->count();
        $withoutEmp = $dailyData->count() - $withEmp;

        // Tipe karyawan breakdown (employee vs visitor)
        $empTypeBreakdown = PobEmployee::whereDate('date', $date)
            ->select('employee_type', DB::raw('COUNT(*) as total'))
            ->groupBy('employee_type')->get()
            ->pluck('total','employee_type');

        // Top departemen 7 hari terakhir untuk heatmap
        $deptWeek = PobEmployee::whereBetween('date', [now()->subDays(6)->toDateString(), now()->toDateString()])
            ->whereNotNull('department')
            ->select('date', 'department', DB::raw('COUNT(*) as total'))
            ->groupBy('date', 'department')
            ->orderBy('date')->orderBy('total','desc')
            ->get();

        $totalCompanies = Company::where('is_active', true)->count();
        $totalEntries   = PobEntry::count();
        $latestDate     = PobEntry::max('date');
        $companies      = Company::where('is_active',true)->orderBy('name')->get();

        return view('dashboard.index', compact(
            'dailyData','dailyTotals','weeklyData','weeklyTotals','monthlyData','monthlyTotals',
            'trendData','weeklyTrend','monthlyTrend',
            'empToday','deptBreakdown','empTypeBreakdown','deptWeek',
            'withEmp','withoutEmp',
            'companies','date','week','month','viewType',
            'totalCompanies','totalEntries','latestDate'
        ));
    }

    public function export(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to   = $request->get('to',   now()->toDateString());

        $entries = PobEntry::with('company')
            ->whereBetween('date',[$from,$to])
            ->whereRaw("id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))")
            ->orderBy('date')->orderBy('company_id')->get();

        return response()->stream(function() use ($entries) {
            $f = fopen('php://output','w');
            fputcsv($f,['Tanggal','Perusahaan','Total POB','Total Manpower','Rasio (%)','Pelapor','Kontak WA','Data Karyawan']);
            foreach ($entries as $e) {
                $ratio = $e->total_manpower>0 ? round($e->total_pob/$e->total_manpower*100,1) : 0;
                fputcsv($f,[
                    $e->date->format('Y-m-d'), $e->company->name??'-',
                    $e->total_pob, $e->total_manpower, $ratio.'%',
                    $e->informed_by, $e->contact_wa,
                    $e->employees()->count()>0 ? 'Ada' : 'Belum',
                ]);
            }
            fclose($f);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="POB_'.$from.'_'.$to.'.csv"',
        ]);
    }
}
