-- ============================================================
-- INSERT DATA PERUSAHAAN
-- Jalankan SETELAH tabel companies sudah ada
-- ============================================================

-- Pastikan kolom slug dan type ada (jalankan ini dulu jika tabel sudah terlanjur dibuat tanpa kolom tersebut)
ALTER TABLE `companies`
    ADD COLUMN IF NOT EXISTS `slug` varchar(255) NULL AFTER `name`,
    ADD COLUMN IF NOT EXISTS `type` varchar(50) NOT NULL DEFAULT 'contractor' AFTER `slug`;

-- Tambahkan unique index slug jika belum ada
ALTER IGNORE TABLE `companies`
    ADD UNIQUE INDEX `companies_slug_unique` (`slug`);

-- ============================================================
-- Insert 36 perusahaan kontraktor
-- ============================================================
INSERT IGNORE INTO companies (name, slug, type, is_active, created_at, updated_at) VALUES
('All Sub-Contractors PB',                      'all-sub-contractors-pb',                      'contractor', 1, NOW(), NOW()),
('All Sub-Contractors REAL',                    'all-sub-contractors-real',                    'contractor', 1, NOW(), NOW()),
('CTCE Group',                                  'ctce-group',                                  'contractor', 1, NOW(), NOW()),
('PT Andalan Duta Eka Nusantara',               'pt-andalan-duta-eka-nusantara',               'contractor', 1, NOW(), NOW()),
('PT Bagong Dekaka Makmur',                     'pt-bagong-dekaka-makmur',                     'contractor', 1, NOW(), NOW()),
('PT Bahana Selaras Alam',                      'pt-bahana-selaras-alam',                      'contractor', 1, NOW(), NOW()),
('PT Bintang Mandiri Perkasa Drill',            'pt-bintang-mandiri-perkasa-drill',            'contractor', 1, NOW(), NOW()),
('PT Bumiindo Mulia Mandiri',                   'pt-bumiindo-mulia-mandiri',                   'contractor', 1, NOW(), NOW()),
('PT Citramegah Karunia Bersama',               'pt-citramegah-karunia-bersama',               'contractor', 1, NOW(), NOW()),
('PT Dahana',                                   'pt-dahana',                                   'contractor', 1, NOW(), NOW()),
('PT Geo Gea Mineralindo',                      'pt-geo-gea-mineralindo',                      'contractor', 1, NOW(), NOW()),
('PT Huayue Nickel Cobalt (SLNC Project)',      'pt-huayue-nickel-cobalt-slnc-project',        'contractor', 1, NOW(), NOW()),
('PT Intertek Utama Services',                  'pt-intertek-utama-services',                  'contractor', 1, NOW(), NOW()),
('PT Inti Karya Pasifik',                       'pt-inti-karya-pasifik',                       'contractor', 1, NOW(), NOW()),
('PT Jakarta Anugerah Mandiri',                 'pt-jakarta-anugerah-mandiri',                 'contractor', 1, NOW(), NOW()),
('PT Karyaindo Ciptanusa',                      'pt-karyaindo-ciptanusa',                      'contractor', 1, NOW(), NOW()),
('PT Lancarjaya Mandiri Abadi',                 'pt-lancarjaya-mandiri-abadi',                 'contractor', 1, NOW(), NOW()),
('PT Merdeka Mining Services',                  'pt-merdeka-mining-services',                  'contractor', 1, NOW(), NOW()),
('PT Mitra Cuan Abadi',                         'pt-mitra-cuan-abadi',                         'contractor', 1, NOW(), NOW()),
('PT Mulia Rentalindo Persada',                 'pt-mulia-rentalindo-persada',                 'contractor', 1, NOW(), NOW()),
('PT Petronesia Benimel (Infrastructure)',      'pt-petronesia-benimel-infrastructure',        'contractor', 1, NOW(), NOW()),
('PT Prima Utama Sultra',                       'pt-prima-utama-sultra',                       'contractor', 1, NOW(), NOW()),
('PT Putra Morowali Sejahtera',                 'pt-putra-morowali-sejahtera',                 'contractor', 1, NOW(), NOW()),
('PT Rajawali Emas Ancora Lestari',             'pt-rajawali-emas-ancora-lestari',             'contractor', 1, NOW(), NOW()),
('PT Samudera Mulia Abadi',                     'pt-samudera-mulia-abadi',                     'contractor', 1, NOW(), NOW()),
('PT Satria Jaya Sultra',                       'pt-satria-jaya-sultra',                       'contractor', 1, NOW(), NOW()),
('PT Serasi Auto Raya',                         'pt-serasi-auto-raya',                         'contractor', 1, NOW(), NOW()),
('PT Sinar Terang Mandiri',                     'pt-sinar-terang-mandiri',                     'contractor', 1, NOW(), NOW()),
('PT Sucofindo',                                'pt-sucofindo',                                'contractor', 1, NOW(), NOW()),
('PT Sumber Semeru Indonesia',                  'pt-sumber-semeru-indonesia',                  'contractor', 1, NOW(), NOW()),
('PT Surveyor Carbon Consulting Indonesia',     'pt-surveyor-carbon-consulting-indonesia',     'contractor', 1, NOW(), NOW()),
('PT Surya Pomala Utama',                       'pt-surya-pomala-utama',                       'contractor', 1, NOW(), NOW()),
('PT Teknologi Infrastruktur Indonesia',        'pt-teknologi-infrastruktur-indonesia',        'contractor', 1, NOW(), NOW()),
('PT Tiga Putra Bungoro',                       'pt-tiga-putra-bungoro',                       'contractor', 1, NOW(), NOW()),
('PT Transkon Jaya',                            'pt-transkon-jaya',                            'contractor', 1, NOW(), NOW()),
('PT Uniteda Arkato',                           'pt-uniteda-arkato',                           'contractor', 1, NOW(), NOW());

-- Verifikasi hasil
SELECT COUNT(*) AS total_perusahaan FROM companies;
