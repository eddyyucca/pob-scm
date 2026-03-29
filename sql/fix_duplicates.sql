-- ============================================================
-- JALANKAN INI DI phpMyAdmin SEBELUM MENGGUNAKAN SISTEM BARU
-- Script ini menghapus data duplikat, menyimpan yang terbaru
-- ============================================================

-- Step 1: Hapus duplikat — simpan MAX(id) per company_id + date
DELETE FROM pob_entries
WHERE id NOT IN (
    SELECT max_id FROM (
        SELECT MAX(id) AS max_id
        FROM pob_entries
        GROUP BY company_id, DATE(date)
    ) AS keep_ids
);

-- Step 2: Verifikasi hasil
SELECT 
    c.name AS perusahaan,
    DATE(p.date) AS tanggal,
    COUNT(*) AS jumlah_entri
FROM pob_entries p
JOIN companies c ON p.company_id = c.id
GROUP BY c.name, DATE(p.date)
HAVING COUNT(*) > 1
ORDER BY jumlah_entri DESC;

-- Jika hasil query di atas kosong, berarti tidak ada duplikat lagi
