<?php
include "connection.php";
include "auth.php";
require_once "permissions.php";

header('Content-Type: application/json');

$canManageTasks = hasFeature("addTask") || in_array(strtolower($loggedRole ?? ''), ["md", "dg", "admin", "administrator", "representative", "committee"]);

if (!$canManageTasks) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    $data = $_POST;
}

$submissions = $data['submissions'] ?? [];
if (empty($submissions) && !empty($data['submission_id'])) {
    $submissions = [
        ['id' => intval($data['submission_id']), 'status' => $data['status'] ?? 'Pending']
    ];
}

if (empty($submissions)) {
    echo json_encode(["success" => false, "message" => "No submissions provided to update"]);
    exit;
}

$updatedCount = 0;
$stmt = $conn->prepare("UPDATE user_task_submissions SET status = ? WHERE id = ?");

if ($stmt) {
    foreach ($submissions as $item) {
        $subId = intval($item['id'] ?? 0);
        $status = trim($item['status'] ?? 'Pending');
        if (!in_array($status, ['Pending', 'Approved'])) {
            $status = 'Pending';
        }
        if ($subId > 0) {
            $stmt->bind_param("si", $status, $subId);
            if ($stmt->execute()) {
                $updatedCount++;
            }
        }
    }
    $stmt->close();
}

echo json_encode([
    "success" => true,
    "message" => "Submissions updated successfully!",
    "updated_count" => $updatedCount
]);
exit;
?>
