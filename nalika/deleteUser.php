<?php
require_once "connection.php";

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