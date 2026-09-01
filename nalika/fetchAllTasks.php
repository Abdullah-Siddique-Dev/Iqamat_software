<?php
// fetchAllTasks.php
header('Content-Type: application/json');
ob_start();
include "connection.php";

$stmt = $conn->prepare(
    "SELECT t.id, t.card, t.category, t.specific_member_id, t.description, 
            t.specifics, t.frequency, t.created_by, t.created_at,
            CONCAT(u.firstName, ' ', u.lastName) as creator_name
     FROM tasks t
     LEFT JOIN users u ON t.created_by = u.id
     ORDER BY t.created_at DESC"
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
