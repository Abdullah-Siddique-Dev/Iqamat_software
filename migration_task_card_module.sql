USE `iqamat`;

-- 1. Add card and category columns to users table
ALTER TABLE `users`
  ADD COLUMN `card` ENUM('Diamond','Gold','Silver') NULL DEFAULT NULL AFTER `area`,
  ADD COLUMN `category` ENUM('A','B','C','D') NULL DEFAULT NULL AFTER `card`;

-- 2. Card change requests table
CREATE TABLE `card_change_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `requested_card` enum('Diamond','Gold','Silver') NOT NULL,
  `requested_category` enum('A','B','C','D') NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `decided_by` int(11) DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_status` (`user_id`, `status`),
  KEY `idx_decided_by` (`decided_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Tasks table
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `card` enum('Diamond','Gold','Silver') DEFAULT NULL,
  `category` enum('A','B','C','D') DEFAULT NULL,
  `specific_member_id` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `specifics` text DEFAULT NULL,
  `frequency` enum('Weekly','Monthly') NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_card_category` (`card`, `category`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Task submissions table
CREATE TABLE `task_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_task_user` (`task_id`, `user_id`),
  KEY `idx_user_submitted` (`user_id`, `submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
