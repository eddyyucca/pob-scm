<?php
namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ContractorContact;
use App\Models\NotificationLog;
use App\Models\PobEntry;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function __construct(private WhatsappService $wa) {}

    // ─── Halaman utama ────────────────────────────────────
    public function index(Request $request)
    {
        $from = $request->get('from', now()->startOfWeek()->toDateString());
        $to   = $request->get('to',   now()->toDateString());

        $reportedRaw = DB::select("
            SELECT company_id,
                   COUNT(DISTINCT DATE(date)) AS days_reported,
                   MAX(date)                  AS last_report,
                   SUM(total_pob)             AS total_pob
            FROM pob_entries
            WHERE date BETWEEN ? AND ?
              AND id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))
            GROUP BY company_id
        ", [$from, $to]);

        $companyIds  = array_column($reportedRaw, 'company_id');
        $companyMap  = Company::whereIn('id', $companyIds)->get()->keyBy('id');

        $reported = collect($reportedRaw)->map(function($r) use ($companyMap) {
            $obj               = new \stdClass();
            $obj->company_id   = $r->company_id;
            $obj->days_reported= $r->days_reported;
            $obj->last_report  = $r->last_report;
            $obj->total_pob    = $r->total_pob;
            $obj->company      = $companyMap->get($r->company_id);
            return $obj;
        });

        $reportedIds = $reported->pluck('company_id')->toArray();

        $notReported = Company::where('is_active', true)
            ->whereNotIn('id', $reportedIds)
            ->withCount(['contacts as contact_count' => fn($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        $reportedCompanies = $reported->map(function ($r) {
            if ($r->company) {
                $r->company->days_reported = $r->days_reported;
                $r->company->last_report   = $r->last_report;
                $r->company->total_pob     = $r->total_pob;
            }
            return $r->company;
        })->filter()->sortBy('name');

        $logs      = NotificationLog::with('company')->orderBy('created_at','desc')->take(50)->get();
        $contacts  = ContractorContact::with('company')->where('is_active',true)->orderBy('company_id')->get();
        $companies = Company::where('is_active',true)->orderBy('name')->get();

        return view('dashboard.notifications', compact(
            'reported','reportedCompanies','notReported',
            'logs','contacts','companies','from','to'
        ));
    }

    // ─── Kirim ke 1 perusahaan (manual, per baris) ───────
    public function sendOne(Request $request)
    {
        $request->validate(['company_id' => 'required|exists:companies,id']);

        $day    = $request->get('day', 'friday');
        $result = $this->wa->sendToCompany((int)$request->company_id, $day);

        $company = Company::find($request->company_id);
        return back()->with('notif_result', [
            'success' => $result['success'],
            'sent'    => $result['sent'],
            'message' => $result['success']
                ? "Notifikasi terkirim ke kontak *{$company->name}*"
                : "Gagal kirim ke {$company->name}: " . $result['response'],
        ]);
    }

    // ─── Kirim ke beberapa perusahaan (checkbox) ─────────
    public function sendReminder(Request $request)
    {
        $request->validate([
            'company_ids'   => 'required|array',
            'company_ids.*' => 'exists:companies,id',
        ]);

        $day    = $request->get('day', 'friday');
        $result = $this->wa->sendToCompanies($request->company_ids, $day);

        return back()->with('notif_result', [
            'success' => $result['success'],
            'sent'    => $result['sent'],
            'message' => $result['success']
                ? $result['sent'] . ' pesan berhasil dikirim'
                : 'Gagal mengirim notifikasi',
        ]);
    }

    // ─── Kirim otomatis semua yang belum lapor ────────────
    public function sendScheduled(Request $request)
    {
        $day    = $request->get('day', 'friday');
        $result = $this->wa->sendScheduledReminder($day);

        $labels = [
            'friday'   => '🔔 Reminder Jumat',
            'saturday' => '⚠️ Peringatan Sabtu',
            'sunday'   => '🚨 Pemanggilan Minggu',
        ];

        return back()->with('notif_result', [
            'success' => $result['success'],
            'sent'    => $result['sent'],
            'message' => $result['sent'] > 0
                ? ($labels[$day] ?? 'Notifikasi') . ': ' . $result['sent'] . ' pesan dikirim ke kontraktor yang belum lapor'
                : ($result['message'] ?? 'Tidak ada kontraktor yang perlu dinotifikasi'),
        ]);
    }

    public function sendFridayAll()
    {
        return $this->sendScheduled(new Request(['day' => 'friday']));
    }

    // ─── CRUD Kontak ──────────────────────────────────────
    public function storeContact(Request $request)
    {
        $v = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name'       => 'required|string|max:100',
            'phone'      => 'required|string|max:20',
            'position'   => 'nullable|string|max:100',
        ]);
        ContractorContact::updateOrCreate(
            ['company_id' => $v['company_id'], 'phone' => $v['phone']],
            $v
        );
        return back()->with('success', 'Kontak berhasil disimpan.');
    }

    public function destroyContact(ContractorContact $contact)
    {
        $contact->delete();
        return back()->with('success', 'Kontak dihapus.');
    }

    public function toggleContact(ContractorContact $contact)
    {
        $contact->update(['is_active' => !$contact->is_active]);
        return back()->with('success', 'Status kontak diperbarui.');
    }
}