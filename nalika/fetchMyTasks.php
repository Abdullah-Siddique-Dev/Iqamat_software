<?php
// fetchMyTasks.php - Get tasks assigned to the logged-in user
header('Content-Type: application/json');
ob_start();
include "connection.php";

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    ob_clean();
    echo json_encode([]);
    exit;
}

// First, get user's card and category
$userStmt = $conn->prepare("SELECT card, category FROM users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows === 0) {
    ob_clean();
    echo json_encode([]);
    $userStmt->close();
    $conn->close();
    exit;
}

$userData = $userResult->fetch_assoc();
$userCard = $userData['card'];
$userCategory = $userData['category'];
$userStmt->close();

// Now fetch tasks that match:
// 1. Tasks assigned to user's card+category (if user has card/category)
// 2. Tasks specifically assigned to this user
$stmt = $conn->prepare(
    "SELECT t.id, t.card, t.category, t.specific_member_id, t.description, 
            t.specifics, t.frequency, t.created_at
     FROM tasks t
     WHERE (t.card = ? AND t.category = ?)
        OR FIND_IN_SET(?, t.specific_member_id) > 0
     ORDER BY t.created_at DESC
     LIMIT 1"
);

$stmt->bind_param("ssi", $userCard, $userCategory, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

ob_clean();
echo json_encode($rows);

$stmt->close();
$conn->close();
