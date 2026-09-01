<?php
include "connection.php";
header('Content-Type: application/json');

$id = $_POST['id'] ?? null;

if (!$id) {
    echo json_encode(["success" => false, "message" => "Missing user ID"]);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET approval_status = 'approved' WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "User approved successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to approve user"]);
}

$stmt->close();
$conn->close();
?>