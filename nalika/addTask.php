<?php
// addTask.php
header('Content-Type: application/json');
ob_start();

include "connection.php";
include "auth.php";
include "permissions.php";

// Check permission
if (!hasFeature("addTask")) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Permission denied.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$card              = isset($data['card']) ? trim($data['card']) : null;
$category          = isset($data['category']) ? trim($data['category']) : null;
$specific_member_id = isset($data['specific_member_id']) ? trim($data['specific_member_id']) : null;
$description       = isset($data['description']) ? trim($data['description']) : '';
$specifics         = isset($data['specifics']) ? trim($data['specifics']) : null;
$frequency         = isset($data['frequency']) ? trim($data['frequency']) : 'Weekly';
$created_by        = isset($data['created_by']) ? (int)$data['created_by'] : $loggedUserId;

// Validation
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

// If specific members, card and category should be null
if ($specific_member_id) {
    $card = null;
    $category = null;
}

try {
    $stmt = $conn->prepare(
        "INSERT INTO tasks
            (card, category, specific_member_id, description, specifics, frequency, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "ssssssi",
        $card, $category, $specific_member_id,
        $description, $specifics, $frequency, $created_by
    );
    $stmt->execute();
    $newId = $conn->insert_id;

    ob_clean();
    echo json_encode(['success' => true, 'id' => $newId]);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
