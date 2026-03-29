-- Jalankan di phpMyAdmin jika tidak bisa pakai artisan migrate
CREATE TABLE IF NOT EXISTS `pob_employees` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `pob_entry_id` bigint UNSIGNED NOT NULL,
  `company_id` bigint UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `id_number` varchar(255) NOT NULL DEFAULT 'N/A',
  `id_type` enum('minepermit','ktp') NOT NULL DEFAULT 'minepermit',
  `name` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `employee_type` enum('employee','visitor') NOT NULL DEFAULT 'employee',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pob_employees_company_id_date_index` (`company_id`,`date`),
  KEY `pob_employees_date_index` (`date`),
  KEY `pob_employees_id_number_index` (`id_number`),
  CONSTRAINT `pob_employees_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pob_employees_pob_entry_id_foreign` FOREIGN KEY (`pob_entry_id`) REFERENCES `pob_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
