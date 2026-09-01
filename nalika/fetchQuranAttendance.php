<?php
/**
 * fetchQuranAttendance.php
 * Returns attendance records for a given user between two dates.
 *
 * GET params:
 *   user_id  int
 *   from     YYYY-MM-DD
 *   to       YYYY-MM-DD
 *
 * Returns JSON: [ { attendance_date, status }, ... ]
 */
include "connection.php";
include "auth.php";

header('Content-Type: application/json');

$userId = (int)($_GET['user_id'] ?? 0);
$from   = $_GET['from'] ?? '';
$to     = $_GET['to']   ?? '';

// Basic validation
if (!$userId || !$from || !$to) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters.']);
    exit;
}

// Security: non-admin users may only fetch their own data
if ((int)$loggedUserId !== $userId && !in_array($loggedRole, ['MD','DG','Admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT attendance_date, status
     FROM quran_attendance
     WHERE user_id = ? AND attendance_date BETWEEN ? AND ?
     ORDER BY attendance_date ASC"
);
$stmt->bind_param('iss', $userId, $from, $to);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = [
        'attendance_date' => $row['attendance_date'],
        'status'          => $row['status'],
    ];
}

echo json_encode($rows);