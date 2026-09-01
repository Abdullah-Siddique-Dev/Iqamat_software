<?php
// Prevent PHP notices/warnings from contaminating HTTP headers
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$httpHost   = $_SERVER['HTTP_HOST'] ?? '';
$serverAddr = $_SERVER['SERVER_ADDR'] ?? '';

if (
    strpos($httpHost, 'localhost') !== false ||
    strpos($httpHost, '127.0.0.1') !== false ||
    $serverAddr === '127.0.0.1' ||
    $serverAddr === '::1' ||
    php_sapi_name() === 'cli'
) {
    // XAMPP Local Database
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "iqamat";
    $port = 3306;
} else {
    // InfinityFree Live Production Database
    $host = "sql202.infinityfree.com";
    $user = "if0_42722491";
    $pass = "1234Nayyab";
    $db   = "if0_42722491_iqamat";
    $port = 3306;
}

mysqli_report(MYSQLI_REPORT_OFF);

try {
    @$conn = new mysqli($host, $user, $pass, $db, $port);
} catch (Throwable $e) {
    $conn = null;
    $connErrMessage = $e->getMessage();
}

if (!$conn || $conn->connect_error) {
    $errMsg = $conn ? $conn->connect_error : ($connErrMessage ?? "Unknown MySQL connection error");
    die("Database connection failed ($host): " . $errMsg);
}

if ($conn) {
    $conn->set_charset("utf8mb4");

    // Auto-ensure required columns exist in users table
    @$conn->query("ALTER TABLE `users` ADD COLUMN `card` VARCHAR(50) NULL");
    @$conn->query("ALTER TABLE `users` ADD COLUMN `category` VARCHAR(50) NULL");

    // Auto-ensure quran_attendance table exists
    @$conn->query("
        CREATE TABLE IF NOT EXISTS `quran_attendance` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL,
            `area_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `attendance_date` DATE NOT NULL,
            `status` ENUM('Present','Absent') NOT NULL DEFAULT 'Absent',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_user_date` (`user_id`, `attendance_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Auto-ensure namaz_attendance table exists & drop restrictive FK constraints
    @$conn->query("
        CREATE TABLE IF NOT EXISTS `namaz_attendance` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL,
            `area_id` INT UNSIGNED NULL DEFAULT 0,
            `attendance_date` DATE NOT NULL,
            `prayer_name` VARCHAR(20) NOT NULL,
            `status` VARCHAR(50) NOT NULL DEFAULT 'missed',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_user_date_prayer` (`user_id`, `attendance_date`, `prayer_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    @$conn->query("ALTER TABLE `namaz_attendance` DROP FOREIGN KEY `fk_namaz_area`");
    @$conn->query("ALTER TABLE `namaz_attendance` DROP FOREIGN KEY `namaz_attendance_ibfk_1`");
    @$conn->query("ALTER TABLE `namaz_attendance` MODIFY COLUMN `area_id` INT UNSIGNED NULL DEFAULT 0");
}