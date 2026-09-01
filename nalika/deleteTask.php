<?php
// deleteTask.php
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
if (!hasFeature("deleteTask")) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Permission denied.']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Missing task ID.']);
    exit;
}

// Delete (CASCADE will remove related submissions)
$stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();
$conn->close();

ob_clean();
if ($affected === 0) {
    echo json_encode(['success' => false, 'message' => 'Task not found.']);
} else {
    echo json_encode(['success' => true, 'message' => 'Task deleted.']);
}
