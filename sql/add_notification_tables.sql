-- Jalankan di phpMyAdmin → tab SQL
-- Buat tabel kontak kontraktor
CREATE TABLE IF NOT EXISTS `contractor_contacts` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_contact_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Buat tabel log notifikasi
CREATE TABLE IF NOT EXISTS `notification_logs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint UNSIGNED NOT NULL,
  `phone` varchar(30) NOT NULL,
  `recipient_name` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('sent','failed','pending') NOT NULL DEFAULT 'pending',
  `response` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_log_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
