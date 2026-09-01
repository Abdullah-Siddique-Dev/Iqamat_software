<?php
include "connection.php";
include "auth.php";

header('Content-Type: application/json');

// Auto-drop restrictive foreign key constraints if present
@$conn->query("ALTER TABLE `namaz_attendance` DROP FOREIGN KEY `fk_namaz_area`");
@$conn->query("ALTER TABLE `namaz_attendance` DROP FOREIGN KEY `namaz_attendance_ibfk_1`");
@$conn->query("ALTER TABLE `namaz_attendance` MODIFY COLUMN `area_id` INT UNSIGNED NULL DEFAULT 0");

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit();
}

$user_id         = intval($input['user_id']         ?? 0);
$area_id         = intval($input['area_id']         ?? 0);
$attendance_date = trim($input['attendance_date']   ?? '');
$prayer_name     = strtolower(trim($input['prayer_name'] ?? ''));
$status          = trim($input['status']            ?? '');

// ── Validation ──────────────────────────────────────────────────────────────
$allowed_prayers = ['fajr','dhuhr','asr','maghrib','isha'];
$allowed_statuses= ['with_jamaat','without_jamaat','missed'];

if (!$user_id || !$attendance_date || !$prayer_name || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

if (!in_array($prayer_name, $allowed_prayers)) {
    echo json_encode(['success' => false, 'message' => 'Invalid prayer name.']);
    exit();
}

if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit();
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $attendance_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
    exit();
}

// ── Block future dates ───────────────────────────────────────────────────────
$today = date('Y-m-d');
if ($attendance_date > $today) {
    echo json_encode(['success' => false, 'message' => 'Cannot mark future dates.']);
    exit();
}

// ── Block dates older than 60 days ───────────────────────────────────────────
$diffDays = (strtotime($today) - strtotime($attendance_date)) / 86400;
if ($diffDays > 60) {
    echo json_encode(['success' => false, 'message' => 'This date is locked (older than 60 days).']);
    exit();
}

// ── Permission Check ─────────────────────────────────────────────────────────
$userRoleLower = strtolower(trim($loggedRole ?? ''));
$canSaveOthers = in_array($userRoleLower, ['md','dg','admin','administrator','committee','representative']);

if ((int)$loggedUserId !== $user_id && !$canSaveOthers) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden permission for this user.']);
    exit();
}

// If area_id <= 0, try to resolve to logged user's area or a valid area ID
if ($area_id <= 0) {
    $area_id = (int)$loggedArea;
}

// ── Upsert (INSERT or UPDATE on duplicate) ───────────────────────────────────
$stmt = $conn->prepare("
    INSERT INTO namaz_attendance (user_id, area_id, attendance_date, prayer_name, status)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        status  = VALUES(status),
        area_id = VALUES(area_id)
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Query error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("iisss", $user_id, $area_id, $attendance_date, $prayer_name, $status);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Saved successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
