<?php
namespace App\Services;

use App\Models\Company;
use App\Models\ContractorContact;
use App\Models\NotificationLog;
use App\Models\PobEntry;
use Carbon\Carbon;

class WhatsappService
{
    private string $token  = 'gLwdMNznAQxXc4TuRDb9';
    private string $apiUrl = 'https://api.fonnte.com/send';

    // ── Kirim bulk via Fonnte data parameter ──────────────
    public function sendBulk(array $targets): array
    {
        if (empty($targets)) return ['success'=>true,'response'=>'no targets','count'=>0];

        $data = array_map(fn($t) => [
            'target'  => $this->formatPhone($t['phone']),
            'message' => $t['message'],
            'delay'   => '2',
        ], $targets);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => ['data' => json_encode($data)],
            CURLOPT_HTTPHEADER     => ['Authorization: ' . $this->token],
        ]);
        $response = curl_exec($curl);
        $error    = curl_error($curl);
        curl_close($curl);

        return ['success'=>!$error, 'response'=>$error?:$response, 'count'=>count($targets)];
    }

    // ── Kirim notifikasi otomatis berdasarkan hari ────────
    // Jumat  = reminder pertama (belum lapor sama sekali minggu ini)
    // Sabtu  = reminder kedua (belum lapor atau kurang dari syarat minimum)
    // Minggu = pemanggilan (belum lapor setelah 2x reminder)
    public function sendScheduledReminder(string $dayType = 'friday'): array
    {
        $weekStart = Carbon::now()->startOfWeek()->toDateString(); // Senin
        $today     = Carbon::now()->toDateString();
        $minDays   = 6; // minimal lapor Senin s/d Sabtu

        // Hitung laporan per perusahaan minggu ini
        $reportCounts = \Illuminate\Support\Facades\DB::select("
            SELECT company_id, COUNT(DISTINCT DATE(date)) AS days_reported
            FROM pob_entries
            WHERE date BETWEEN ? AND ?
              AND id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))
            GROUP BY company_id
        ", [$weekStart, $today]);

        $reportMap    = collect($reportCounts)->pluck('days_reported','company_id');
        $allIds       = Company::where('is_active',true)->pluck('id');

        // Tentukan target berdasarkan hari
        $targetIds = match($dayType) {
            // Jumat: belum lapor sama sekali minggu ini
            'friday'   => $allIds->filter(fn($id) => ($reportMap[$id] ?? 0) == 0)->values(),
            // Sabtu: belum mencapai minimal (belum lapor hari ini = hari ke-6)
            'saturday' => $allIds->filter(fn($id) => ($reportMap[$id] ?? 0) < 6)->values(),
            // Minggu: masih belum lengkap setelah 2x diingatkan
            'sunday'   => $allIds->filter(fn($id) => ($reportMap[$id] ?? 0) < 6)->values(),
            default    => collect(),
        };

        if ($targetIds->isEmpty()) {
            return ['sent'=>0,'success'=>true,'message'=>'Semua kontraktor sudah lapor minggu ini.'];
        }

        $contacts = ContractorContact::whereIn('company_id', $targetIds)
            ->where('is_active', true)
            ->with('company')
            ->get();

        if ($contacts->isEmpty()) {
            return ['sent'=>0,'success'=>true,'message'=>'Tidak ada kontak aktif untuk dihubungi.'];
        }

        $targets = $contacts->map(fn($c) => [
            'phone'   => $c->phone,
            'name'    => $c->name,
            'message' => $this->buildMessage(
                $c->name,
                $c->company->name,
                (int)($reportMap[$c->company_id] ?? 0),
                $dayType,
                $weekStart,
                $today
            ),
        ])->toArray();

        $result = $this->sendBulk($targets);

        // Log
        foreach ($contacts as $c) {
            NotificationLog::create([
                'company_id'     => $c->company_id,
                'phone'          => $c->phone,
                'recipient_name' => $c->name,
                'message'        => $this->buildMessage($c->name, $c->company->name,
                    (int)($reportMap[$c->company_id] ?? 0), $dayType, $weekStart, $today),
                'status'         => $result['success'] ? 'sent' : 'failed',
                'response'       => $result['response'],
                'sent_at'        => now(),
            ]);
        }

        return ['sent'=>count($targets),'success'=>$result['success'],'response'=>$result['response']];
    }

    // ── Kirim manual ke perusahaan tertentu ───────────────
    public function sendToCompanies(array $companyIds, string $dayType = 'friday'): array
    {
        $weekStart = Carbon::now()->startOfWeek()->toDateString();
        $today     = Carbon::now()->toDateString();

        $reportCounts = \Illuminate\Support\Facades\DB::select("
            SELECT company_id, COUNT(DISTINCT DATE(date)) AS days_reported
            FROM pob_entries WHERE date BETWEEN ? AND ?
              AND id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))
            GROUP BY company_id
        ", [$weekStart, $today]);
        $reportMap = collect($reportCounts)->pluck('days_reported','company_id');

        $contacts = ContractorContact::whereIn('company_id', $companyIds)
            ->where('is_active', true)->with('company')->get();

        if ($contacts->isEmpty()) return ['sent'=>0,'success'=>false,'message'=>'Tidak ada kontak aktif.'];

        $targets = $contacts->map(fn($c) => [
            'phone'   => $c->phone,
            'name'    => $c->name,
            'message' => $this->buildMessage($c->name, $c->company->name,
                (int)($reportMap[$c->company_id] ?? 0), $dayType, $weekStart, $today),
        ])->toArray();

        $result = $this->sendBulk($targets);

        foreach ($contacts as $c) {
            NotificationLog::create([
                'company_id'     => $c->company_id,
                'phone'          => $c->phone,
                'recipient_name' => $c->name,
                'message'        => $this->buildMessage($c->name, $c->company->name,
                    (int)($reportMap[$c->company_id] ?? 0), $dayType, $weekStart, $today),
                'status'         => $result['success'] ? 'sent' : 'failed',
                'response'       => $result['response'],
                'sent_at'        => now(),
            ]);
        }

        return ['sent'=>count($targets),'success'=>$result['success']];
    }

    // ── Template pesan berdasarkan konteks ────────────────
    private function buildMessage(string $name, string $company, int $daysReported,
                                  string $dayType, string $weekStart, string $today): string
    {
        $startFmt = Carbon::parse($weekStart)->locale('id')->isoFormat('D MMMM');
        $todayFmt = Carbon::parse($today)->locale('id')->isoFormat('D MMMM YYYY');
        $url      = config('app.url', 'http://localhost/pob');

        $header = "Yth. Bapak/Ibu *{$name}*\ndari *{$company}*\n\n";

        if ($dayType === 'friday') {
            return $header
                . "🔔 *REMINDER LAPORAN POB & MP — MINGGU INI*\n\n"
                . "Kami mengingatkan bahwa laporan harian MP & POB untuk periode "
                . "*{$startFmt} – {$todayFmt}* belum kami terima.\n\n"
                . "Mohon segera mengisi laporan melalui:\n🔗 {$url}\n\n"
                . "Laporan digunakan untuk *rapat manajemen Selasa*.\n\n"
                . "Terima kasih.\n_PT Sulawesi Cahaya Mineral_";
        }

        if ($dayType === 'saturday') {
            return $header
                . "⚠️ *PENGINGAT KEDUA — HARI TERAKHIR LAPORAN*\n\n"
                . "Hingga hari ini, laporan yang kami terima dari perusahaan Anda "
                . "baru *{$daysReported} hari* dari target minimal *6 hari* dalam seminggu.\n\n"
                . "Hari ini *Sabtu* adalah hari terakhir pengisian laporan mingguan.\n\n"
                . "Segera isi laporan: 🔗 {$url}\n\n"
                . "_PT Sulawesi Cahaya Mineral_";
        }

        if ($dayType === 'sunday') {
            return $header
                . "🚨 *PEMANGGILAN — LAPORAN BELUM LENGKAP*\n\n"
                . "Hingga akhir minggu ini, laporan dari perusahaan Anda "
                . "hanya *{$daysReported} hari* dari minimal *6 hari*.\n\n"
                . "Hal ini akan dicatat dalam evaluasi performa kontraktor minggu ini.\n\n"
                . "Mohon hubungi tim SCM untuk klarifikasi atau segera isi laporan:\n🔗 {$url}\n\n"
                . "Terima kasih atas perhatiannya.\n_PT Sulawesi Cahaya Mineral_";
        }

        return $header . "Mohon segera mengisi laporan POB: {$url}";
    }

    private function formatPhone(string $phone): string
    {
        $p = preg_replace('/\D/', '', $phone);
        if (str_starts_with($p, '0')) $p = '62' . substr($p, 1);
        return $p;
    }
}