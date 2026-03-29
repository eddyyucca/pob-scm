<?php
namespace App\Services;

use App\Models\Company;
use App\Models\ContractorContact;
use App\Models\NotificationLog;
use App\Models\PobEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WhatsappService
{
    private string $token  = 'gLwdMNznAQxXc4TuRDb9';
    private string $apiUrl = 'https://api.fonnte.com/send';
    private string $appUrl = 'http://pob.scmtms.online/';

    // ── Kirim bulk via Fonnte ─────────────────────────────
    public function sendBulk(array $targets): array
    {
        if (empty($targets)) return ['success'=>true,'response'=>'no targets','count'=>0];

        $data = array_map(fn($t) => [
            'target'  => $this->formatPhone($t['phone']),
            'message' => $t['message'],
            'delay'   => '3',
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

    // ── Kirim ke 1 perusahaan saja ───────────────────────
    public function sendSingle(string $phone, string $message): array
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => [
                'target'      => $this->formatPhone($phone),
                'message'     => $message,
                'countryCode' => '62',
            ],
            CURLOPT_HTTPHEADER => ['Authorization: ' . $this->token],
        ]);
        $response = curl_exec($curl);
        $error    = curl_error($curl);
        curl_close($curl);

        return ['success'=>!$error, 'response'=>$error?:$response];
    }

    // ── Kirim scheduled otomatis ─────────────────────────
    // Jumat  = reminder (0 hari lapor minggu ini)
    // Sabtu  = peringatan hari terakhir (masih 0 hari)
    // Minggu = pemanggilan (masih 0 hari)
    public function sendScheduledReminder(string $dayType = 'friday'): array
    {
        $weekStart = Carbon::now()->startOfWeek()->toDateString();
        $today     = Carbon::now()->toDateString();

        // Hitung laporan per perusahaan minggu ini
        $reportCounts = DB::select("
            SELECT company_id, COUNT(DISTINCT DATE(date)) AS days_reported
            FROM pob_entries
            WHERE date BETWEEN ? AND ?
              AND id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))
            GROUP BY company_id
        ", [$weekStart, $today]);

        $reportMap = collect($reportCounts)->pluck('days_reported', 'company_id');
        $allIds    = Company::where('is_active', true)->pluck('id');

        // Hanya kirim ke yang BELUM LAPOR SAMA SEKALI (0 hari)
        $targetIds = $allIds->filter(fn($id) => (int)($reportMap[$id] ?? 0) === 0)->values();

        if ($targetIds->isEmpty()) {
            return ['sent'=>0,'success'=>true,'message'=>'Semua kontraktor sudah ada laporan minggu ini.'];
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
            'message' => $this->buildMessage($c->name, $c->company->name, $dayType, $weekStart, $today),
        ])->toArray();

        $result = $this->sendBulk($targets);

        foreach ($contacts as $c) {
            NotificationLog::create([
                'company_id'     => $c->company_id,
                'phone'          => $c->phone,
                'recipient_name' => $c->name,
                'message'        => $this->buildMessage($c->name, $c->company->name, $dayType, $weekStart, $today),
                'status'         => $result['success'] ? 'sent' : 'failed',
                'response'       => $result['response'],
                'sent_at'        => now(),
            ]);
        }

        return ['sent'=>count($targets), 'success'=>$result['success'], 'response'=>$result['response']];
    }

    // ── Kirim manual ke 1 perusahaan ─────────────────────
    public function sendToCompany(int $companyId, string $dayType = 'friday'): array
    {
        $weekStart = Carbon::now()->startOfWeek()->toDateString();
        $today     = Carbon::now()->toDateString();

        $contacts = ContractorContact::where('company_id', $companyId)
            ->where('is_active', true)
            ->with('company')
            ->get();

        if ($contacts->isEmpty()) {
            return ['sent'=>0,'success'=>false,'message'=>'Tidak ada kontak aktif untuk perusahaan ini.'];
        }

        $sent = 0;
        $lastResult = [];
        foreach ($contacts as $c) {
            $msg    = $this->buildMessage($c->name, $c->company->name, $dayType, $weekStart, $today);
            $result = $this->sendSingle($c->phone, $msg);

            NotificationLog::create([
                'company_id'     => $c->company_id,
                'phone'          => $c->phone,
                'recipient_name' => $c->name,
                'message'        => $msg,
                'status'         => $result['success'] ? 'sent' : 'failed',
                'response'       => $result['response'],
                'sent_at'        => now(),
            ]);

            if ($result['success']) $sent++;
            $lastResult = $result;
        }

        return ['sent'=>$sent, 'success'=>$sent > 0, 'response'=>$lastResult['response'] ?? ''];
    }

    // ── Kirim manual ke beberapa perusahaan ──────────────
    public function sendToCompanies(array $companyIds, string $dayType = 'friday'): array
    {
        $totalSent = 0;
        foreach ($companyIds as $id) {
            $r = $this->sendToCompany((int)$id, $dayType);
            $totalSent += $r['sent'];
        }
        return ['sent'=>$totalSent, 'success'=>$totalSent > 0];
    }

    // ── Template pesan ────────────────────────────────────
    public function buildMessage(string $name, string $company, string $dayType,
                                  string $weekStart, string $today): string
    {
        $startFmt = Carbon::parse($weekStart)->locale('id')->isoFormat('D MMMM');
        $todayFmt = Carbon::parse($today)->locale('id')->isoFormat('D MMMM YYYY');
        $url      = $this->appUrl;

        // Footer otomatis
        $footer = "\n\n_Pesan ini dikirim otomatis oleh Sistem POB PT Sulawesi Cahaya Mineral._";

        $header = "Yth. Bapak/Ibu *{$name}*\ndari *{$company}*\n\n";

        if ($dayType === 'friday') {
            return $header
                . "🔔 *REMINDER LAPORAN POB & MP*\n\n"
                . "Kami mengingatkan bahwa laporan harian MP & POB untuk periode minggu ini "
                . "(*{$startFmt} – {$todayFmt}*) belum kami terima dari perusahaan Anda.\n\n"
                . "Mohon segera mengisi laporan melalui:\n"
                . "🔗 {$url}"
                . $footer;
        }

        if ($dayType === 'saturday') {
            return $header
                . "⚠️ *PENGINGAT — HARI TERAKHIR LAPORAN MINGGU INI*\n\n"
                . "Laporan MP & POB dari perusahaan Anda untuk periode "
                . "*{$startFmt} – {$todayFmt}* belum kami terima.\n\n"
                . "Hari ini *Sabtu* adalah hari terakhir pengisian laporan mingguan.\n\n"
                . "Segera isi laporan:\n"
                . "🔗 {$url}"
                . $footer;
        }

        if ($dayType === 'sunday') {
            return $header
                . "🚨 *PEMANGGILAN — LAPORAN MINGGU INI BELUM ADA*\n\n"
                . "Hingga hari Minggu ini, kami belum menerima laporan MP & POB dari "
                . "perusahaan Anda untuk minggu *{$startFmt} – {$todayFmt}*.\n\n"
                . "Hal ini akan dicatat dalam evaluasi performa kontraktor.\n\n"
                . "Mohon segera hubungi tim SCM atau isi laporan:\n"
                . "🔗 {$url}"
                . $footer;
        }

        // Manual / default
        return $header
            . "📋 *PEMBERITAHUAN LAPORAN POB & MP*\n\n"
            . "Mohon segera mengisi laporan harian MP & POB melalui:\n"
            . "🔗 {$url}"
            . $footer;
    }

    private function formatPhone(string $phone): string
    {
        $p = preg_replace('/\D/', '', $phone);
        if (str_starts_with($p, '0')) $p = '62' . substr($p, 1);
        return $p;
    }
}