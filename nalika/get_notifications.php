<?php
session_start();
header('Content-Type: application/json');
include "connection.php";

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(["unread" => 0, "notifications" => []]);
    exit;
}

$userId = $_SESSION['user']['id'];

$stmt = $conn->prepare("
    SELECT id, message, type, is_read, created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
$unread = 0;

while ($row = $result->fetch_assoc()) {
    if ($row['is_read'] == 0) $unread++;
    $notifications[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode([
    "unread" => $unread,
    "notifications" => $notifications
]);
?>