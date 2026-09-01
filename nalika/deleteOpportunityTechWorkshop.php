<?php
include "connection.php";
session_start();
if (!isset($_SESSION['user'])) { exit("Unauthorized"); }

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    echo "Invalid ID.";
    exit;
}

$stmt = $conn->prepare("DELETE FROM technical_workshops WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "Technical Workshop deleted successfully!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>