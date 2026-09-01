<?php
include "connection.php";
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

header('Content-Type: application/json');

$user_id = intval($_GET['user_id'] ?? 0);
$from    = $_GET['from'] ?? '';
$to      = $_GET['to']   ?? '';

// Basic validation
if (!$user_id || !$from || !$to) {
    echo json_encode([]);
    exit();
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    echo json_encode([]);
    exit();
}

// Only allow the logged-in user to fetch their own data
if ($_SESSION['user']['id'] != $user_id) {
    http_response_code(403);
    echo json_encode([]);
    exit();
}

$stmt = $conn->prepare("
    SELECT attendance_date, prayer_name, status
    FROM namaz_attendance
    WHERE user_id = ?
      AND attendance_date BETWEEN ? AND ?
    ORDER BY attendance_date ASC, FIELD(prayer_name,'fajr','dhuhr','asr','maghrib','isha')
");
$stmt->bind_param("iss", $user_id, $from, $to);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($rows);
?>