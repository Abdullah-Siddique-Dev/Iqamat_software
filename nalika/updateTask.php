<?php
// updateTask.php
header('Content-Type: application/json');
ob_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . "/connection.php";

session_start();
if (!isset($_SESSION['user'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$loggedUser = $_SESSION['user'];
$loggedRole = strtolower(trim($loggedUser['role'] ?? ''));

// Quick permission check
include "permissions.php";
if (!hasFeature("editTask")) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Permission denied.']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
    exit;
}

$id                = isset($data['id']) ? (int)$data['id'] : 0;
$card              = isset($data['card']) ? trim($data['card']) : null;
$category          = isset($data['category']) ? trim($data['category']) : null;
$specific_member_id = isset($data['specific_member_id']) ? trim($data['specific_member_id']) : null;
$description       = isset($data['description']) ? trim($data['description']) : '';
$specifics         = isset($data['specifics']) ? trim($data['specifics']) : null;
$frequency         = isset($data['frequency']) ? trim($data['frequency']) : 'Weekly';

// Validate
if (!$id) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Missing task ID.']);
    exit;
}

if (!$description) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Description is required.']);
    exit;
}

// Must have either card+category OR specific_member_id
if ((!$card || !$category) && !$specific_member_id) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Must specify either Card+Category or Specific Members.']);
    exit;
}

// If specific members, nullify card and category
if ($specific_member_id) {
    $card = null;
    $category = null;
}

// Update
$stmt = $conn->prepare(
    "UPDATE tasks
     SET card = ?,
         category = ?,
         specific_member_id = ?,
         description = ?,
         specifics = ?,
         frequency = ?
     WHERE id = ?"
);

$stmt->bind_param(
    "ssssssi",
    $card,
    $category,
    $specific_member_id,
    $description,
    $specifics,
    $frequency,
    $id
);

$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();
$conn->close();

ob_clean();
if ($affected === 0) {
    echo json_encode(['success' => true, 'message' => 'No changes detected.']);
} else {
    echo json_encode(['success' => true, 'message' => 'Task updated.']);
}
