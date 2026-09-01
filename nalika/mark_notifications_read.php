<?php
session_start();
header('Content-Type: application/json');
include "connection.php";

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(["success" => false]);
    exit;
}

$userId = $_SESSION['user']['id'];

$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(["success" => true]);
?>