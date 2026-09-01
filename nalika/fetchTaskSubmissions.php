<?php
include "connection.php";
include "auth.php";
require_once "permissions.php";

header('Content-Type: application/json');

$canManageTasks = hasFeature("addTask") || in_array(strtolower($loggedRole ?? ''), ["md", "dg", "admin", "administrator", "representative", "committee"]);

if (!$canManageTasks) {
    echo json_encode(["error" => "Unauthorized access"]);
    exit;
}

$taskId = intval($_GET['task_id'] ?? 0);
if ($taskId <= 0) {
    echo json_encode([]);
    exit;
}

$query = "SELECT uts.*, u.firstName, u.lastName, u.username, u.area, u.role, t.task_code, t.expiry_date 
          FROM user_task_submissions uts 
          JOIN users u ON uts.user_id = u.id 
          LEFT JOIN admin_tasks t ON uts.task_id = t.id
          WHERE uts.task_id = '$taskId' 
          ORDER BY uts.submitted_at DESC";

$result = mysqli_query($conn, $query);
$submissions = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $submittedTime = !empty($row['submitted_at']) ? strtotime($row['submitted_at']) : 0;
        $expiryTime = !empty($row['expiry_date']) ? strtotime($row['expiry_date']) : 0;
        $row['is_late'] = ($expiryTime > 0 && $submittedTime > $expiryTime);
        $submissions[] = $row;
    }
}

echo json_encode($submissions);
?>
