<?php
include "connection.php";
header('Content-Type: application/json');

// ✅ Turn PHP errors into JSON instead of HTML so the frontend never breaks
error_reporting(E_ALL);
ini_set('display_errors', 0); // don't print raw HTML errors
set_error_handler(function ($errno, $errstr) {
    echo json_encode(["error" => true, "message" => $errstr]);
    exit;
});

$stmt = $conn->prepare("
    SELECT id, firstName, lastName, username, email, phone, area, role, image
    FROM users
    WHERE is_active = 1 AND approval_status = 'pending'
");

if (!$stmt) {
    echo json_encode(["error" => true, "message" => $conn->error]);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users);

$stmt->close();
$conn->close();
?>