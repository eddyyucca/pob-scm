-- ============================================================
-- DATA DUMMY: Kontak WA Kontraktor
-- Jalankan SETELAH insert_companies.sql
-- PENTING: Ganti nomor 0812345670xx dengan nomor WA asli
--          agar bisa test kirim notifikasi sungguhan
-- ============================================================

-- Hapus data kontak lama jika ada
-- DELETE FROM contractor_contacts;

INSERT IGNORE INTO contractor_contacts (company_id, name, phone, position, is_active, created_at, updated_at)
SELECT c.id, k.name, k.phone, k.position, 1, NOW(), NOW()
FROM companies c
JOIN (VALUES
    ('All Sub-Contractors PB',           'Budi Santoso',       '081234567001', 'Site Supervisor'),
    ('All Sub-Contractors REAL',         'Andi Wijaya',        '081234567002', 'Safety Officer'),
    ('CTCE Group',                       'Chen Wei',           '081234567003', 'Project Manager'),
    ('CTCE Group',                       'Li Ming',            '081234567004', 'HSE Officer'),
    ('PT Andalan Duta Eka Nusantara',    'Rudi Hartono',       '081234567005', 'Supervisor'),
    ('PT Bagong Dekaka Makmur',          'Siti Rahayu',        '081234567006', 'Admin Lapangan'),
    ('PT Bahana Selaras Alam',           'Dedi Kurniawan',     '081234567007', 'Koordinator'),
    ('PT Bintang Mandiri Perkasa Drill', 'Agus Setiawan',      '081234567008', 'Drill Supervisor'),
    ('PT Bumiindo Mulia Mandiri',        'Hendra Pratama',     '081234567009', 'Site Manager'),
    ('PT Citramegah Karunia Bersama',    'Wahyu Nugroho',      '081234567010', 'PIC Laporan'),
    ('PT Dahana',                        'Irwan Susanto',      '081234567011', 'HSE Manager'),
    ('PT Dahana',                        'Dewi Lestari',       '081234567012', 'Admin'),
    ('PT Geo Gea Mineralindo',           'Fajar Maulana',      '081234567013', 'Supervisor'),
    ('PT Huayue Nickel Cobalt (SLNC Project)', 'Zhang Wei',   '081234567014', 'Project Coordinator'),
    ('PT Intertek Utama Services',       'Anton Budiman',      '081234567015', 'Inspector'),
    ('PT Inti Karya Pasifik',            'Rizky Firmansyah',   '081234567016', 'Site Officer'),
    ('PT Jakarta Anugerah Mandiri',      'Bambang Wicaksono',  '081234567017', 'Kepala Proyek'),
    ('PT Karyaindo Ciptanusa',           'Suparman',           '081234567018', 'Mandor'),
    ('PT Lancarjaya Mandiri Abadi',      'Toni Setiabudi',     '081234567019', 'Supervisor'),
    ('PT Merdeka Mining Services',       'Galih Permana',      '081234567020', 'Mining Supervisor'),
    ('PT Merdeka Mining Services',       'Nadia Putri',        '081234567021', 'HR Officer'),
    ('PT Mitra Cuan Abadi',              'Yusuf Hidayat',      '081234567022', 'Site Admin'),
    ('PT Mulia Rentalindo Persada',      'Rahmat Hidayat',     '081234567023', 'Fleet Supervisor'),
    ('PT Petronesia Benimel (Infrastructure)', 'Eko Prasetyo', '081234567024', 'Infra Coordinator'),
    ('PT Prima Utama Sultra',            'Lukman Hakim',       '081234567025', 'Kepala Regu'),
    ('PT Putra Morowali Sejahtera',      'Mansur Rahim',       '081234567026', 'Site Manager'),
    ('PT Rajawali Emas Ancora Lestari',  'Farid Wajdi',        '081234567027', 'Supervisor'),
    ('PT Samudera Mulia Abadi',          'Hendri Kurniawan',   '081234567028', 'Logistics Officer'),
    ('PT Satria Jaya Sultra',            'Darwis Hamzah',      '081234567029', 'Kepala Tim'),
    ('PT Serasi Auto Raya',              'Sigit Prabowo',      '081234567030', 'Fleet Manager'),
    ('PT Sinar Terang Mandiri',          'Arif Budiman',       '081234567031', 'Site Supervisor'),
    ('PT Sucofindo',                     'Yanti Susanti',      '081234567032', 'Inspector'),
    ('PT Sucofindo',                     'Doni Prasetya',      '081234567033', 'Field Officer'),
    ('PT Sumber Semeru Indonesia',       'Bayu Saputra',       '081234567034', 'Koordinator'),
    ('PT Surveyor Carbon Consulting Indonesia', 'Indra Gunawan','081234567035','Surveyor Senior'),
    ('PT Surya Pomala Utama',            'Nurdin Saleh',       '081234567036', 'Site Manager'),
    ('PT Teknologi Infrastruktur Indonesia', 'Haris Setiawan', '081234567037', 'Tech Supervisor'),
    ('PT Tiga Putra Bungoro',            'Safruddin',          '081234567038', 'Mandor Utama'),
    ('PT Transkon Jaya',                 'Willy Santoso',      '081234567039', 'Transport Manager'),
    ('PT Uniteda Arkato',                'Muchlis Hamid',       '081234567040', 'Site Supervisor')
) AS k(company_name, name, phone, position)
ON c.name = k.company_name;

-- Verifikasi
SELECT c.name AS perusahaan, cc.name AS kontak, cc.phone, cc.position
FROM contractor_contacts cc
JOIN companies c ON cc.company_id = c.id
ORDER BY c.name;
