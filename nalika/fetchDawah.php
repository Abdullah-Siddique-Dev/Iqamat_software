<?php
// fetchDawah.php
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
    "SELECT id, user_id, dawah_type, dawah_mode, person_name, person_contact,
            location, dawah_date, remarks, created_at
     FROM dawah_records
     WHERE user_id = ?
     ORDER BY dawah_date DESC, created_at DESC"
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