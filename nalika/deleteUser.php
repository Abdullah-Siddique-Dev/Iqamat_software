<?php
require_once "connection.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    echo "Unauthorized";
    exit;
}
$userRole = strtolower(trim($_SESSION['user']['role'] ?? ''));
if (in_array($userRole, ['member', 'trainee'])) {
    echo "Forbidden: Members cannot delete users";
    exit;
}

$id = $_GET['id'] ?? 0;

// امنیت check
if (!is_numeric($id)) {
    echo "Invalid ID";
    exit;
}

// Prepared statement (SAFE)
$stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

echo "Deleted";
?>