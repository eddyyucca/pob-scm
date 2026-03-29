<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ContractorContact;
use App\Models\PobEmployee;
use App\Models\PobEntry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding users...');
        $this->seedUsers();

        $this->command->info('Seeding companies...');
        $companies = $this->seedCompanies();

        $this->command->info('Seeding contractor contacts (kontak WA)...');
        $this->seedContacts($companies);

        $this->command->info('Seeding POB entries (laporan dummy)...');
        $this->seedPobEntries($companies);

        $this->command->info('✅ Selesai! Data dummy berhasil dibuat.');
        $this->command->table(
            ['Data', 'Jumlah'],
            [
                ['Users',             User::count()],
                ['Perusahaan',        Company::count()],
                ['Kontak WA',         ContractorContact::count()],
                ['Laporan POB',       PobEntry::count()],
                ['Data Karyawan',     PobEmployee::count()],
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────
    private function seedUsers(): void
    {
        $users = [
            ['name'=>'Admin SCM',        'email'=>'admin@scmnickel.com',  'role'=>'admin'],
            ['name'=>'Manager Operasi',  'email'=>'manager@scmnickel.com','role'=>'admin'],
            ['name'=>'Viewer SCM',       'email'=>'viewer@scmnickel.com', 'role'=>'viewer'],
        ];
        foreach ($users as $u) {
            User::firstOrCreate(
                ['email' => $u['email']],
                ['name'=>$u['name'], 'password'=>Hash::make('password123'), 'role'=>$u['role']]
            );
        }
    }

    // ─────────────────────────────────────────────────────────────
    private function seedCompanies(): \Illuminate\Support\Collection
    {
        $names = [
            'All Sub-Contractors PB',
            'All Sub-Contractors REAL',
            'CTCE Group',
            'PT Andalan Duta Eka Nusantara',
            'PT Bagong Dekaka Makmur',
            'PT Bahana Selaras Alam',
            'PT Bintang Mandiri Perkasa Drill',
            'PT Bumiindo Mulia Mandiri',
            'PT Citramegah Karunia Bersama',
            'PT Dahana',
            'PT Geo Gea Mineralindo',
            'PT Huayue Nickel Cobalt (SLNC Project)',
            'PT Intertek Utama Services',
            'PT Inti Karya Pasifik',
            'PT Jakarta Anugerah Mandiri',
            'PT Karyaindo Ciptanusa',
            'PT Lancarjaya Mandiri Abadi',
            'PT Merdeka Mining Services',
            'PT Mitra Cuan Abadi',
            'PT Mulia Rentalindo Persada',
            'PT Petronesia Benimel (Infrastructure)',
            'PT Prima Utama Sultra',
            'PT Putra Morowali Sejahtera',
            'PT Rajawali Emas Ancora Lestari',
            'PT Samudera Mulia Abadi',
            'PT Satria Jaya Sultra',
            'PT Serasi Auto Raya',
            'PT Sinar Terang Mandiri',
            'PT Sucofindo',
            'PT Sumber Semeru Indonesia',
            'PT Surveyor Carbon Consulting Indonesia',
            'PT Surya Pomala Utama',
            'PT Teknologi Infrastruktur Indonesia',
            'PT Tiga Putra Bungoro',
            'PT Transkon Jaya',
            'PT Uniteda Arkato',
        ];

        foreach ($names as $name) {
            Company::firstOrCreate(
                ['name' => $name],
                ['slug'=>Str::slug($name), 'type'=>'contractor', 'is_active'=>true]
            );
        }

        return Company::orderBy('id')->get();
    }

    // ─────────────────────────────────────────────────────────────
    // Kontak WA — GANTI nomor di sini dengan nomor asli Anda untuk test kirim WA
    // ─────────────────────────────────────────────────────────────
    private function seedContacts(\Illuminate\Support\Collection $companies): void
    {
        // Data kontak dummy — format: [nama_perusahaan_partial => [[nama, nomor, jabatan], ...]]
        // Nomor menggunakan format 08xx — otomatis dikonversi ke 628xx saat kirim
        $contactData = [
            'All Sub-Contractors PB'          => [['Budi Santoso',       '081234567001', 'Site Supervisor']],
            'All Sub-Contractors REAL'        => [['Andi Wijaya',         '081234567002', 'Safety Officer']],
            'CTCE Group'                      => [['Chen Wei',            '081234567003', 'Project Manager'],
                                                   ['Li Ming',             '081234567004', 'HSE Officer']],
            'PT Andalan Duta Eka Nusantara'   => [['Rudi Hartono',        '081234567005', 'Supervisor']],
            'PT Bagong Dekaka Makmur'         => [['Siti Rahayu',         '081234567006', 'Admin Lapangan']],
            'PT Bahana Selaras Alam'          => [['Dedi Kurniawan',      '081234567007', 'Koordinator']],
            'PT Bintang Mandiri Perkasa Drill'=> [['Agus Setiawan',       '081234567008', 'Drill Supervisor']],
            'PT Bumiindo Mulia Mandiri'       => [['Hendra Pratama',      '081234567009', 'Site Manager']],
            'PT Citramegah Karunia Bersama'   => [['Wahyu Nugroho',       '081234567010', 'PIC Laporan']],
            'PT Dahana'                       => [['Irwan Susanto',       '081234567011', 'HSE Manager'],
                                                   ['Dewi Lestari',        '081234567012', 'Admin']],
            'PT Geo Gea Mineralindo'          => [['Fajar Maulana',       '081234567013', 'Supervisor']],
            'PT Huayue Nickel Cobalt'         => [['Zhang Wei',           '081234567014', 'Project Coordinator']],
            'PT Intertek Utama Services'      => [['Anton Budiman',       '081234567015', 'Inspector']],
            'PT Inti Karya Pasifik'           => [['Rizky Firmansyah',    '081234567016', 'Site Officer']],
            'PT Jakarta Anugerah Mandiri'     => [['Bambang Wicaksono',   '081234567017', 'Kepala Proyek']],
            'PT Karyaindo Ciptanusa'          => [['Suparman',            '081234567018', 'Mandor']],
            'PT Lancarjaya Mandiri Abadi'     => [['Toni Setiabudi',      '081234567019', 'Supervisor']],
            'PT Merdeka Mining Services'      => [['Galih Permana',       '081234567020', 'Mining Supervisor'],
                                                   ['Nadia Putri',         '081234567021', 'HR Officer']],
            'PT Mitra Cuan Abadi'             => [['Yusuf Hidayat',       '081234567022', 'Site Admin']],
            'PT Mulia Rentalindo Persada'     => [['Rahmat Hidayat',      '081234567023', 'Fleet Supervisor']],
            'PT Petronesia Benimel'           => [['Eko Prasetyo',        '081234567024', 'Infra Coordinator']],
            'PT Prima Utama Sultra'           => [['Lukman Hakim',        '081234567025', 'Kepala Regu']],
            'PT Putra Morowali Sejahtera'     => [['Mansur Rahim',        '081234567026', 'Site Manager']],
            'PT Rajawali Emas Ancora Lestari' => [['Farid Wajdi',         '081234567027', 'Supervisor']],
            'PT Samudera Mulia Abadi'         => [['Hendri Kurniawan',    '081234567028', 'Logistics Officer']],
            'PT Satria Jaya Sultra'           => [['Darwis Hamzah',       '081234567029', 'Kepala Tim']],
            'PT Serasi Auto Raya'             => [['Sigit Prabowo',       '081234567030', 'Fleet Manager']],
            'PT Sinar Terang Mandiri'         => [['Arif Budiman',        '081234567031', 'Site Supervisor']],
            'PT Sucofindo'                    => [['Yanti Susanti',       '081234567032', 'Inspector'],
                                                   ['Doni Prasetya',       '081234567033', 'Field Officer']],
            'PT Sumber Semeru Indonesia'      => [['Bayu Saputra',        '081234567034', 'Koordinator']],
            'PT Surveyor Carbon Consulting'   => [['Indra Gunawan',       '081234567035', 'Surveyor Senior']],
            'PT Surya Pomala Utama'           => [['Nurdin Saleh',        '081234567036', 'Site Manager']],
            'PT Teknologi Infrastruktur'      => [['Haris Setiawan',      '081234567037', 'Tech Supervisor']],
            'PT Tiga Putra Bungoro'           => [['Safruddin',           '081234567038', 'Mandor Utama']],
            'PT Transkon Jaya'                => [['Willy Santoso',       '081234567039', 'Transport Manager']],
            'PT Uniteda Arkato'               => [['Muchlis Hamid',       '081234567040', 'Site Supervisor']],
        ];

        foreach ($companies as $company) {
            // Cocokkan nama perusahaan (partial match)
            foreach ($contactData as $partial => $contacts) {
                if (str_contains($company->name, $partial) || str_contains($partial, explode(' ', $company->name)[0])) {
                    foreach ($contacts as [$name, $phone, $position]) {
                        ContractorContact::firstOrCreate(
                            ['company_id' => $company->id, 'phone' => $phone],
                            ['name'=>$name, 'position'=>$position, 'is_active'=>true]
                        );
                    }
                    break;
                }
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    private function seedPobEntries(\Illuminate\Support\Collection $companies): void
    {
        // Data karyawan dummy per jabatan
        $dummyEmployees = [
            ['jabatan'=>'Operator Alat Berat',  'dept'=>'Mining Operations'],
            ['jabatan'=>'Safety Officer',        'dept'=>'HSE'],
            ['jabatan'=>'Mekanik Senior',        'dept'=>'Maintenance'],
            ['jabatan'=>'Supervisor Tambang',    'dept'=>'Mining Operations'],
            ['jabatan'=>'Juru Ledak',            'dept'=>'Blasting'],
            ['jabatan'=>'Operator Dump Truck',   'dept'=>'Hauling'],
            ['jabatan'=>'Teknisi Listrik',       'dept'=>'Engineering'],
            ['jabatan'=>'Administrasi Lapangan', 'dept'=>'Administration'],
            ['jabatan'=>'Surveyor',              'dept'=>'Survey'],
            ['jabatan'=>'Driver',                'dept'=>'Transport'],
        ];

        $namaDepan = ['Ahmad','Budi','Dedi','Eko','Fajar','Galih','Hendra','Irwan','Joko','Kiki',
                      'Lukman','Maman','Nanda','Oki','Putu','Rudi','Sandi','Tono','Udin','Wahyu',
                      'Yogi','Zainal','Agus','Bambang','Candra','Doni','Edwin','Faisal','Guntur','Haris'];
        $namaBelakang = ['Santoso','Wijaya','Kurniawan','Setiawan','Pratama','Nugroho','Hidayat',
                         'Permana','Saputra','Firmansyah','Prasetyo','Budiman','Wicaksono','Susanto','Hartono'];

        // Generate laporan 30 hari terakhir
        // Hanya 70% perusahaan yang lapor setiap hari (sisanya "belum lapor" untuk test notifikasi)
        $reportingCompanies = $companies->take(26); // 26 dari 36 yang lapor rutin
        $today = Carbon::today();

        foreach ($reportingCompanies as $company) {
            // Tiap perusahaan lapor 20-28 hari dari 30 hari terakhir
            $daysToReport = rand(20, 28);
            $reportedDays = collect(range(0, 29))
                ->shuffle()
                ->take($daysToReport)
                ->sort()
                ->values();

            foreach ($reportedDays as $daysAgo) {
                $date    = $today->copy()->subDays($daysAgo)->toDateString();
                $pob     = rand(15, 120);
                $mp      = $pob + rand(5, 40); // manpower selalu >= pob

                $entry = PobEntry::firstOrCreate(
                    ['company_id' => $company->id, 'date' => $date],
                    [
                        'total_pob'      => $pob,
                        'total_manpower' => $mp,
                        'informed_by'    => $namaDepan[array_rand($namaDepan)] . ' ' . $namaBelakang[array_rand($namaBelakang)],
                        'contact_wa'     => '0812' . rand(10000000, 99999999),
                    ]
                );

                // Buat data karyawan hanya untuk 7 hari terakhir (tidak perlu semua)
                if ($daysAgo <= 7 && PobEmployee::where('pob_entry_id', $entry->id)->count() === 0) {
                    $employees = [];
                    for ($k = 0; $k < $pob; $k++) {
                        $jabatan  = $dummyEmployees[$k % count($dummyEmployees)];
                        $nama     = $namaDepan[array_rand($namaDepan)] . ' ' . $namaBelakang[array_rand($namaBelakang)];
                        $idNumber = 'MP-' . date('Y', strtotime($date)) . '-' . str_pad(($company->id * 1000 + $k), 6, '0', STR_PAD_LEFT);
                        $employees[] = [
                            'pob_entry_id'  => $entry->id,
                            'company_id'    => $company->id,
                            'date'          => $date,
                            'id_number'     => $idNumber,
                            'id_type'       => 'minepermit',
                            'name'          => $nama,
                            'position'      => $jabatan['jabatan'],
                            'department'    => $jabatan['dept'],
                            'employee_type' => 'employee',
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ];
                    }
                    // Insert per chunk agar tidak timeout
                    foreach (array_chunk($employees, 100) as $chunk) {
                        PobEmployee::insert($chunk);
                    }
                }
            }
        }
    }
}
