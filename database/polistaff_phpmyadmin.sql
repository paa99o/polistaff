CREATE DATABASE IF NOT EXISTS `polistaff_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `polistaff_db`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `feedbacks`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `member_documents`;
DROP TABLE IF EXISTS `expense_claims`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `payment_submissions`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `attendances`;
DROP TABLE IF EXISTS `activity_registrations`;
DROP TABLE IF EXISTS `activities`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `ic_number` VARCHAR(30) NULL,
    `email` VARCHAR(255) NOT NULL,
    `email_verified_at` TIMESTAMP NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('member', 'treasurer', 'chairman', 'admin') NOT NULL DEFAULT 'member',
    `department` VARCHAR(255) NULL,
    `phone` VARCHAR(255) NULL,
    `membership_status` ENUM('pending', 'active', 'inactive') NOT NULL DEFAULT 'pending',
    `joined_date` DATE NULL,
    `fee_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    UNIQUE KEY `users_ic_number_unique` (`ic_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
    `email` VARCHAR(255) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
    `id` VARCHAR(255) NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `payload` LONGTEXT NOT NULL,
    `last_activity` INT NOT NULL,
    PRIMARY KEY (`id`),
    KEY `sessions_user_id_index` (`user_id`),
    KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activities` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `date_time` DATETIME NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `max_participants` INT UNSIGNED NULL,
    `status` ENUM('draft', 'pending_approval', 'approved', 'cancelled') NOT NULL DEFAULT 'draft',
    `qr_code_token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `activities_qr_code_token_unique` (`qr_code_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attendances` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `activity_id` BIGINT UNSIGNED NOT NULL,
    `scanned_at` DATETIME NOT NULL,
    `qr_code_token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `attendances_user_id_activity_id_unique` (`user_id`, `activity_id`),
    KEY `attendances_activity_id_foreign` (`activity_id`),
    CONSTRAINT `attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `attendances_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_registrations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `activity_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('registered', 'cancelled') NOT NULL DEFAULT 'registered',
    `registered_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `activity_registrations_user_id_activity_id_unique` (`user_id`, `activity_id`),
    KEY `activity_registrations_activity_id_foreign` (`activity_id`),
    CONSTRAINT `activity_registrations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `activity_registrations_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `type` ENUM('income', 'expense') NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `receipt_number` VARCHAR(255) NOT NULL,
    `transaction_date` DATE NOT NULL,
    `category` VARCHAR(255) NOT NULL,
    `payment_method` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `transactions_receipt_number_unique` (`receipt_number`),
    KEY `transactions_user_id_foreign` (`user_id`),
    CONSTRAINT `transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` VARCHAR(255) NOT NULL DEFAULT 'info',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `link` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `notifications_user_id_foreign` (`user_id`),
    CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `feedbacks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `activity_id` BIGINT UNSIGNED NULL,
    `content` TEXT NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `feedbacks_user_id_foreign` (`user_id`),
    KEY `feedbacks_activity_id_foreign` (`activity_id`),
    CONSTRAINT `feedbacks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `feedbacks_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payment_submissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `reviewed_by` BIGINT UNSIGNED NULL,
    `transaction_id` BIGINT UNSIGNED NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_method` VARCHAR(255) NOT NULL,
    `payment_date` DATE NOT NULL,
    `proof_path` VARCHAR(255) NOT NULL,
    `notes` TEXT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `review_notes` TEXT NULL,
    `reviewed_at` DATETIME NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `payment_submissions_user_id_foreign` (`user_id`),
    KEY `payment_submissions_reviewed_by_foreign` (`reviewed_by`),
    KEY `payment_submissions_transaction_id_foreign` (`transaction_id`),
    CONSTRAINT `payment_submissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `payment_submissions_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `payment_submissions_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `system_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(255) NOT NULL,
    `value` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `system_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `expense_claims` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `reviewed_by` BIGINT UNSIGNED NULL,
    `transaction_id` BIGINT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `category` VARCHAR(255) NOT NULL,
    `claim_date` DATE NOT NULL,
    `receipt_path` VARCHAR(255) NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `review_notes` TEXT NULL,
    `reviewed_at` DATETIME NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `expense_claims_user_id_foreign` (`user_id`),
    KEY `expense_claims_reviewed_by_foreign` (`reviewed_by`),
    KEY `expense_claims_transaction_id_foreign` (`transaction_id`),
    CONSTRAINT `expense_claims_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `expense_claims_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `expense_claims_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `member_documents` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `document_type` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `member_documents_user_id_foreign` (`user_id`),
    CONSTRAINT `member_documents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(255) NOT NULL,
    `module` VARCHAR(255) NOT NULL,
    `record_type` VARCHAR(255) NULL,
    `record_id` BIGINT UNSIGNED NULL,
    `description` TEXT NOT NULL,
    `changes` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `audit_logs_user_id_foreign` (`user_id`),
    CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `ic_number`, `email`, `email_verified_at`, `password`, `role`, `department`, `phone`, `membership_status`, `joined_date`, `fee_balance`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin PoliBest', '900101110001', 'admin@polibest.test', NULL, '$2y$12$ysOHj4iu6n3dHUSDZ2Dny.X5yuGOUv2szDkRPmtngFL1fAXvWTPY2', 'admin', 'JTMK', '0111111001', 'active', CURDATE(), 0.00, NULL, NOW(), NOW()),
(2, 'Bendahari PoliBest', '900101110002', 'treasurer@polibest.test', NULL, '$2y$12$ysOHj4iu6n3dHUSDZ2Dny.X5yuGOUv2szDkRPmtngFL1fAXvWTPY2', 'treasurer', 'JTMK', '0111111002', 'active', CURDATE(), 0.00, NULL, NOW(), NOW()),
(3, 'Pengerusi PoliBest', '900101110003', 'chairman@polibest.test', NULL, '$2y$12$ysOHj4iu6n3dHUSDZ2Dny.X5yuGOUv2szDkRPmtngFL1fAXvWTPY2', 'chairman', 'JTMK', '0111111003', 'active', CURDATE(), 0.00, NULL, NOW(), NOW()),
(4, 'Muhammad Hilmi Aqil Bin Zulkifli', '900101110004', 'hilmi@polibest.test', NULL, '$2y$12$ysOHj4iu6n3dHUSDZ2Dny.X5yuGOUv2szDkRPmtngFL1fAXvWTPY2', 'member', 'JTMK', '0111111004', 'active', CURDATE(), 20.00, NULL, NOW(), NOW()),
(5, 'Muhammad Fayyad Aqel Bin Mohd Faizal', '900101110005', 'fayyad@polibest.test', NULL, '$2y$12$ysOHj4iu6n3dHUSDZ2Dny.X5yuGOUv2szDkRPmtngFL1fAXvWTPY2', 'member', 'JTMK', '0111111005', 'active', CURDATE(), 20.00, NULL, NOW(), NOW()),
(6, 'Muhammad Afiq Azfar Bin Ramli', '900101110006', 'afiq@polibest.test', NULL, '$2y$12$ysOHj4iu6n3dHUSDZ2Dny.X5yuGOUv2szDkRPmtngFL1fAXvWTPY2', 'member', 'JTMK', '0111111006', 'active', CURDATE(), 20.00, NULL, NOW(), NOW());

INSERT INTO `activities` (`id`, `title`, `description`, `date_time`, `location`, `max_participants`, `status`, `qr_code_token`, `created_at`, `updated_at`) VALUES
(1, 'Mesyuarat Agung Tahunan PoliBest', 'Program tahunan kelab staf Politeknik Besut.', DATE_ADD(NOW(), INTERVAL 7 DAY), 'Dewan Seminar Politeknik Besut', 80, 'approved', 'polibest-agm-2026-token', NOW(), NOW()),
(2, 'Program Sukan Kelab Staf', 'Aktiviti sukan untuk mengeratkan hubungan staf.', DATE_ADD(NOW(), INTERVAL 14 DAY), 'Padang Politeknik Besut', 100, 'approved', 'polibest-sukan-2026-token', NOW(), NOW());

INSERT INTO `transactions` (`id`, `user_id`, `type`, `amount`, `description`, `receipt_number`, `transaction_date`, `category`, `payment_method`, `created_at`, `updated_at`) VALUES
(1, 4, 'income', 20.00, 'Bayaran yuran bulanan', 'PB-202607290001', CURDATE(), 'Yuran', 'Tunai', NOW(), NOW()),
(2, NULL, 'expense', 50.00, 'Pembelian makanan mesyuarat', 'PB-202607290002', CURDATE(), 'Aktiviti', 'Tunai', NOW(), NOW());

INSERT INTO `attendances` (`id`, `user_id`, `activity_id`, `scanned_at`, `qr_code_token`, `created_at`, `updated_at`) VALUES
(1, 4, 1, NOW(), 'polibest-agm-2026-token', NOW(), NOW());

INSERT INTO `activity_registrations` (`id`, `user_id`, `activity_id`, `status`, `registered_at`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 'registered', NOW(), NOW(), NOW()),
(2, 5, 1, 'registered', NOW(), NOW(), NOW());

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `link`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Selamat datang ke PoliBest', 'Sistem Pengurusan Kelab Staf telah sedia digunakan.', 'info', 0, '/activities/1', NOW(), NOW()),
(2, 4, 'Peringatan tunggakan yuran', 'Baki yuran anda ialah RM 20.00.', 'fee', 0, '/dashboard', NOW(), NOW());

INSERT INTO `feedbacks` (`id`, `user_id`, `activity_id`, `content`, `rating`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 'Sistem mudah digunakan dan memudahkan rekod kehadiran aktiviti.', 5, NOW(), NOW());

INSERT INTO `payment_submissions` (`id`, `user_id`, `reviewed_by`, `transaction_id`, `amount`, `payment_method`, `payment_date`, `proof_path`, `notes`, `status`, `review_notes`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 5, NULL, NULL, 20.00, 'Online Transfer', CURDATE(), 'payment-proofs/sample-proof.pdf', 'Bayaran yuran bulanan', 'pending', NULL, NULL, NOW(), NOW());

INSERT INTO `system_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 'club_name', 'PoliBest', NOW(), NOW()),
(2, 'monthly_fee', '20', NOW(), NOW()),
(3, 'receipt_prefix', 'PB', NOW(), NOW()),
(4, 'contact_email', 'admin@polibest.test', NOW(), NOW());

INSERT INTO `expense_claims` (`id`, `user_id`, `reviewed_by`, `transaction_id`, `title`, `description`, `amount`, `category`, `claim_date`, `receipt_path`, `status`, `review_notes`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 4, NULL, NULL, 'Tuntutan makanan program', 'Makanan ringan untuk program kelab staf.', 35.00, 'Aktiviti', CURDATE(), 'expense-claims/sample-receipt.pdf', 'pending', NULL, NULL, NOW(), NOW());

INSERT INTO `member_documents` (`id`, `user_id`, `title`, `document_type`, `file_path`, `created_at`, `updated_at`) VALUES
(1, 4, 'Salinan Kad Pengenalan', 'IC', 'member-documents/sample-ic.pdf', NOW(), NOW());

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `module`, `record_type`, `record_id`, `description`, `changes`, `ip_address`, `created_at`, `updated_at`) VALUES
(1, 1, 'created', 'Financial Transaction', 'App\\Models\\Transaction', 1, 'Created income transaction PB-202607290001.', JSON_OBJECT('type', 'income', 'amount', 20.00, 'category', 'Yuran'), '127.0.0.1', NOW(), NOW()),
(2, 4, 'created', 'QR Attendance', 'App\\Models\\Attendance', 1, 'Attendance recorded automatically for Mesyuarat Agung Tahunan PoliBest.', JSON_OBJECT('activity', 'Mesyuarat Agung Tahunan PoliBest'), '127.0.0.1', NOW(), NOW());
