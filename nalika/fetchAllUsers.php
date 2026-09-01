<?php
// fetchAllUsers.php - for multi-select dropdown in adminTasks.php
header('Content-Type: application/json');
ob_start();
include "connection.php";

$stmt = $conn->prepare(
    "SELECT id, firstName, lastName, username, role, card, category
     FROM users
     WHERE is_active = 1 AND approval_status = 'approved'
     ORDER BY firstName, lastName"
);

if (!$stmt) {
    ob_clean();
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

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
