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

        $wp = explode('-W', strtoupper($week));
        $yr = $wp[0] ?? now()->format('o');
        $wn = str_pad($wp[1] ?? now()->format('W'), 2, '0', STR_PAD_LEFT);

        $latest = "SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date)";

        // DAILY
        $dailyData = PobEntry::with('company')
            ->whereDate('date', $date)
            ->whereRaw("id IN ($latest)")
            ->orderBy('total_pob','desc')->get();
        $dP = $dailyData->sum('total_pob'); $dM = $dailyData->sum('total_manpower');
        $dailyTotals = ['pob'=>$dP,'mp'=>$dM,'ratio'=>$dM>0?round($dP/$dM*100,1):0,'reporters'=>$dailyData->count()];

        // WEEKLY
        try { $ws = date('Y-m-d', strtotime("{$yr}-W{$wn}-1")); }
        catch (\Exception $e) { $ws = now()->startOfWeek()->toDateString(); }

        $weeklyData = PobEntry::with('company')
            ->selectRaw('company_id, SUM(total_pob) as pob, SUM(total_manpower) as mp, COUNT(DISTINCT DATE(date)) as days')
            ->whereRaw('YEARWEEK(date,1)=YEARWEEK(?,1)',[$ws])
            ->whereRaw("id IN ($latest)")
            ->groupBy('company_id')->orderBy('pob','desc')->get();
        $wP=$weeklyData->sum('pob'); $wM=$weeklyData->sum('mp');
        $weeklyTotals=['pob'=>$wP,'mp'=>$wM,'ratio'=>$wM>0?round($wP/$wM*100,1):0,'reporters'=>$weeklyData->count()];

        // MONTHLY
        [$mY,$mM] = array_pad(explode('-',$month),2,'01');
        $monthlyData = PobEntry::with('company')
            ->selectRaw('company_id, SUM(total_pob) as pob, SUM(total_manpower) as mp, COUNT(DISTINCT DATE(date)) as days')
            ->whereYear('date',$mY)->whereMonth('date',$mM)
            ->whereRaw("id IN ($latest)")
            ->groupBy('company_id')->orderBy('pob','desc')->get();
        $mP=$monthlyData->sum('pob'); $mM2=$monthlyData->sum('mp');
        $monthlyTotals=['pob'=>$mP,'mp'=>$mM2,'ratio'=>$mM2>0?round($mP/$mM2*100,1):0,'reporters'=>$monthlyData->count()];

        // TRENDS — ambil sebagai array PHP lalu encode sekali di view
        $trendDaily = DB::select("
            SELECT DATE(date) as d,
                   SUM(total_pob) as pob,
                   SUM(total_manpower) as mp
            FROM pob_entries WHERE id IN ($latest) AND date>=?
            GROUP BY DATE(date)
            ORDER BY DATE(date) ASC
        ", [now()->subDays(29)->toDateString()]);

        $trendWeekly = DB::select("
            SELECT MIN(DATE(date)) as d,
                   YEARWEEK(date,1) as yw,
                   SUM(total_pob) as pob,
                   SUM(total_manpower) as mp
            FROM pob_entries WHERE id IN ($latest) AND date>=?
            GROUP BY YEARWEEK(date,1)
            ORDER BY yw ASC
        ", [now()->subWeeks(11)->startOfWeek()->toDateString()]);

        $trendMonthly = DB::select("
            SELECT DATE_FORMAT(date,'%Y-%m-01') as d,
                   SUM(total_pob) as pob,
                   SUM(total_manpower) as mp
            FROM pob_entries WHERE id IN ($latest) AND date>=?
            GROUP BY DATE_FORMAT(date,'%Y-%m'), DATE_FORMAT(date,'%Y-%m-01')
            ORDER BY d ASC
        ", [now()->subMonths(11)->startOfMonth()->toDateString()]);

        // EMPLOYEE STATS
        $empToday = PobEmployee::whereDate('date',$date)->count();
        $deptData = PobEmployee::whereDate('date',$date)
            ->whereNotNull('department')
            ->select('department', DB::raw('COUNT(*) as total'))
            ->groupBy('department')->orderBy('total','desc')->take(10)->get();
        $empEmployee = PobEmployee::whereDate('date',$date)->where('employee_type','employee')->count();
        $empVisitor  = PobEmployee::whereDate('date',$date)->where('employee_type','visitor')->count();

        $withEmp    = PobEntry::whereDate('date',$date)->whereRaw("id IN ($latest)")->whereHas('employees')->count();
        $withoutEmp = $dailyData->count() - $withEmp;

        $totalCompanies = Company::where('is_active',true)->count();
        $totalEntries   = PobEntry::count();
        $latestDate     = PobEntry::max('date');
        $companies      = Company::where('is_active',true)->orderBy('name')->get();

        return view('dashboard.index', compact(
            'dailyData','dailyTotals','weeklyData','weeklyTotals','monthlyData','monthlyTotals',
            'trendDaily','trendWeekly','trendMonthly',
            'empToday','deptData','empEmployee','empVisitor',
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
            fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM untuk Excel
            fputcsv($f,['Tanggal','Perusahaan','Total POB','Total Manpower','Rasio (%)','Pelapor','Kontak WA']);
            foreach ($entries as $e) {
                fputcsv($f,[$e->date->format('Y-m-d'),$e->company->name??'-',
                    $e->total_pob,$e->total_manpower,
                    ($e->total_manpower>0?round($e->total_pob/$e->total_manpower*100,1):0).'%',
                    $e->informed_by,$e->contact_wa]);
            }
            fclose($f);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="POB_'.$from.'_'.$to.'.csv"',
        ]);
    }
}
