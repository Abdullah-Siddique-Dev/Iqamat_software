<?php
// deleteDawah.php
header('Content-Type: application/json');
ob_start();

// ── Database Connection ──
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . "/connection.php";

// ── Read params ──
$id      = isset($_GET['id'])      ? (int)$_GET['id']      : 0;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// ── Validate ──
if (!$id) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Missing record ID.']);
    exit;
}
if (!$user_id) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Missing user ID.']);
    exit;
}

// ── Delete (user_id check = users can only delete their own records) ──
$stmt = $conn->prepare(
    "DELETE FROM dawah_records WHERE id = ? AND user_id = ?"
);

$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();
$conn->close();

ob_clean();
if ($affected === 0) {
    echo json_encode(['success' => false, 'message' => 'Record not found or access denied.']);
} else {
    echo json_encode(['success' => true, 'message' => 'Record deleted.']);
}