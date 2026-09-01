<?php
include "connection.php";
include "auth.php";

header('Content-Type: application/json');

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

// Read JSON body
$input  = json_decode(file_get_contents('php://input'), true);
$userId = (int)($input['user_id']        ?? 0);
$areaId = (int)($input['area_id']        ?? 0);
$date   = trim($input['attendance_date'] ?? '');
$status = trim($input['status']          ?? '');

// Validate
if (!$userId || !$date || !in_array($status, ['Present','Absent'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters provided.']);
    exit;
}

// Security: users may save their own records OR authorized roles can mark attendance
$userRoleLower = strtolower(trim($loggedRole ?? ''));
$canSaveOthers = in_array($userRoleLower, ['md','dg','admin','administrator','committee','representative']);

if ((int)$loggedUserId !== $userId && !$canSaveOthers) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden permission to mark for this user.']);
    exit;
}

// Block future dates
if ($date > date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'Cannot mark future dates.']);
    exit;
}

// Block dates older than 60 days
$maxPastDate = date('Y-m-d', strtotime('-60 days'));
if ($date < $maxPastDate) {
    echo json_encode(['success' => false, 'message' => 'Date is locked (older than 60 days).']);
    exit;
}

// Upsert
$stmt = $conn->prepare(
    "INSERT INTO quran_attendance (user_id, area_id, attendance_date, status)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE status = VALUES(status), area_id = VALUES(area_id)"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
    exit;
}

$stmt->bind_param('iiss', $userId, $areaId, $date, $status);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => "Marked {$status} for {$date}."]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database execution error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>