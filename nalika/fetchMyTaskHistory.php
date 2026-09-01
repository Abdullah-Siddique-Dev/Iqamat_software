<?php
// fetchMyTaskHistory.php - Get user's task submission history
header('Content-Type: application/json');
ob_start();
include "connection.php";

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    ob_clean();
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT ts.id, ts.task_id, ts.user_id, ts.start_time, ts.end_time, 
            ts.document_path, ts.submitted_at,
            t.description as task_description
     FROM task_submissions ts
     LEFT JOIN tasks t ON ts.task_id = t.id
     WHERE ts.user_id = ?
     ORDER BY ts.submitted_at DESC"
);

if (!$stmt) {
    ob_clean();
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

ob_clean();
echo json_encode($rows);

$stmt->close();
$conn->close();
