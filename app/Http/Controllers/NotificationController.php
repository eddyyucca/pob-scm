<?php
namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ContractorContact;
use App\Models\NotificationLog;
use App\Models\PobEntry;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function __construct(private WhatsappService $wa) {}

    // ─── Halaman utama notifikasi ─────────────────────────
    public function index(Request $request)
    {
        $from = $request->get('from', now()->startOfWeek()->toDateString());
        $to   = $request->get('to',   now()->toDateString());

        // Perusahaan sudah lapor dalam rentang
        // Gunakan DB::select agar tidak kena ONLY_FULL_GROUP_BY
        $reportedRaw = \Illuminate\Support\Facades\DB::select("
            SELECT
                company_id,
                COUNT(DISTINCT DATE(date))  AS days_reported,
                MAX(date)                   AS last_report,
                SUM(total_pob)              AS total_pob
            FROM pob_entries
            WHERE date BETWEEN ? AND ?
              AND id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))
            GROUP BY company_id
        ", [$from, $to]);

        $companyIds = array_column($reportedRaw, 'company_id');
        $companies_map = \App\Models\Company::whereIn('id', $companyIds)->get()->keyBy('id');

        $reported = collect($reportedRaw)->map(function($r) use ($companies_map) {
            $obj = new \stdClass();
            $obj->company_id   = $r->company_id;
            $obj->days_reported= $r->days_reported;
            $obj->last_report  = $r->last_report;
            $obj->total_pob    = $r->total_pob;
            $obj->company      = $companies_map->get($r->company_id);
            return $obj;
        });

        $reportedIds = $reported->pluck('company_id')->toArray();

        // Perusahaan belum lapor
        $notReported = Company::where('is_active', true)
            ->whereNotIn('id', $reportedIds)
            ->withCount(['contacts as contact_count' => fn($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        // Perusahaan sudah lapor (dengan info kontak)
        $reportedCompanies = $reported->map(function ($r) {
            $r->company->days_reported = $r->days_reported;
            $r->company->last_report   = $r->last_report;
            $r->company->total_pob     = $r->total_pob;
            return $r->company;
        })->sortBy('name');

        // Log notifikasi terbaru
        $logs = NotificationLog::with('company')
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        // Kontak semua perusahaan
        $contacts  = ContractorContact::with('company')->where('is_active', true)->orderBy('company_id')->get();
        $companies = Company::where('is_active', true)->orderBy('name')->get();

        return view('dashboard.notifications', compact(
            'reported', 'reportedCompanies', 'notReported',
            'logs', 'contacts', 'companies',
            'from', 'to'
        ));
    }

    // ─── Kirim reminder manual ────────────────────────────
    public function sendReminder(Request $request)
    {
        $request->validate([
            'company_ids' => 'required|array',
            'company_ids.*' => 'exists:companies,id',
        ]);

        $contacts = ContractorContact::whereIn('company_id', $request->company_ids)
            ->where('is_active', true)
            ->with('company')
            ->get();

        if ($contacts->isEmpty()) {
            return back()->with('error', 'Tidak ada kontak aktif untuk perusahaan yang dipilih.');
        }

        $targets = $contacts->map(fn($c) => [
            'phone'   => $c->phone,
            'name'    => $c->name,
            'message' => $this->buildMessage($c->name, $c->company->name),
        ])->toArray();

        $result = $this->wa->sendBulk($targets);

        foreach ($contacts as $c) {
            NotificationLog::create([
                'company_id'     => $c->company_id,
                'phone'          => $c->phone,
                'recipient_name' => $c->name,
                'message'        => $this->buildMessage($c->name, $c->company->name),
                'status'         => $result['success'] ? 'sent' : 'failed',
                'response'       => $result['response'],
                'sent_at'        => now(),
            ]);
        }

        return back()->with('notif_result', [
            'success' => $result['success'],
            'sent'    => count($targets),
            'message' => $result['success']
                ? count($targets) . ' pesan berhasil dikirim via WhatsApp'
                : 'Gagal mengirim: ' . $result['response'],
        ]);
    }

    // ─── Kirim scheduled (Jumat/Sabtu/Minggu) ────────────
    public function sendScheduled(Request $request)
    {
        $day    = $request->get('day', 'friday'); // friday | saturday | sunday
        $result = $this->wa->sendScheduledReminder($day);

        $labels = ['friday'=>'Reminder Jumat','saturday'=>'Peringatan Sabtu','sunday'=>'Pemanggilan Minggu'];
        return back()->with('notif_result', [
            'success' => $result['success'],
            'sent'    => $result['sent'],
            'message' => $result['sent'] > 0
                ? ($labels[$day]??'Notifikasi') . ': ' . $result['sent'] . ' pesan dikirim'
                : ($result['message'] ?? 'Tidak ada yang perlu dinotifikasi'),
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

    private function buildMessage(string $name, string $company): string
    {
        $week = Carbon::now()->startOfWeek()->locale('id')->isoFormat('D MMMM');
        $end  = Carbon::now()->locale('id')->isoFormat('D MMMM YYYY');
        return "Yth. Bapak/Ibu *{$name}*\ndari *{$company}*\n\n"
             . "⚠️ *PENGINGAT LAPORAN POB & MANPOWER*\n\n"
             . "Laporan harian MP & POB periode *{$week} – {$end}* belum kami terima.\n\n"
             . "Mohon segera mengisi melalui:\n"
             . "🔗 " . config('app.url', 'http://localhost/pob') . "\n\n"
             . "Digunakan untuk rapat manajemen tiap Selasa.\n\n"
             . "Terima kasih.\n_PT Sulawesi Cahaya Mineral_";
    }
}