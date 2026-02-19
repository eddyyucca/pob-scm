<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::firstOrCreate(
            ['email' => 'admin@scmnickel.com'],
            [
                'name'     => 'Admin SCM',
                'password' => Hash::make('password123'),
                'role'     => 'admin',
            ]
        );

        // Perusahaan dari data Excel aktual (36 mitra)
        $companies = [
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

        foreach ($companies as $name) {
            Company::firstOrCreate(
                ['name' => $name],
                [
                    'slug'      => Str::slug($name),
                    'type'      => 'contractor',
                    'is_active' => true,
                ]
            );
        }
    }
}
