<?php
include "connection.php";
include "auth.php";
// $loggedUserId, $loggedRole, $loggedArea set by auth.php

require_once "permissions.php";
if (!hasPermission("adminTasks")) {
    header("Location: dashboard.php");
    exit();
}

$canManageTasks = hasFeature("addTask") || in_array(strtolower($loggedRole ?? ''), ["md", "dg", "admin", "administrator", "representative", "committee"]);

// Auto-create admin_tasks table if it doesn't exist
$createTableSql = "CREATE TABLE IF NOT EXISTS `admin_tasks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `task_code` VARCHAR(50) NULL,
  `task_name` VARCHAR(255) NULL,
  `card` VARCHAR(50) NULL,
  `category` VARCHAR(50) NULL,
  `specific_member_id` INT NULL,
  `description` TEXT NOT NULL,
  `specifics` TEXT NULL,
  `expiry_date` DATETIME NULL,
  `frequency` VARCHAR(50) DEFAULT 'Weekly',
  `created_by` VARCHAR(100) DEFAULT 'admin admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $createTableSql);

// Ensure new columns exist if table was created previously
@mysqli_query($conn, "ALTER TABLE `admin_tasks` ADD COLUMN `task_code` VARCHAR(50) NULL AFTER `category`");
@mysqli_query($conn, "ALTER TABLE `admin_tasks` ADD COLUMN `task_name` VARCHAR(255) NULL AFTER `task_code`");
@mysqli_query($conn, "ALTER TABLE `admin_tasks` ADD COLUMN `specific_member_id` VARCHAR(255) NULL AFTER `category`");
@mysqli_query($conn, "ALTER TABLE `admin_tasks` MODIFY COLUMN `specific_member_id` VARCHAR(255) NULL");
@mysqli_query($conn, "ALTER TABLE `admin_tasks` ADD COLUMN `specifics` TEXT NULL AFTER `description`");
@mysqli_query($conn, "ALTER TABLE `admin_tasks` ADD COLUMN `expiry_date` DATETIME NULL AFTER `specifics`");

// Helper function to generate Task Code (e.g. GD5, GD11, DB1)
function generateTaskCode($conn, $card, $category, $specificMemberIds = null) {
    $cardVal = !empty($card) ? trim($card) : '';
    $catVal = !empty($category) ? trim($category) : '';

    if (!empty($specificMemberIds) && (empty($cardVal) || empty($catVal))) {
        if (is_array($specificMemberIds)) {
            $firstId = intval($specificMemberIds[0] ?? 0);
        } else {
            $parts = explode(',', $specificMemberIds);
            $firstId = intval($parts[0] ?? 0);
        }
        if ($firstId > 0) {
            $uRes = mysqli_query($conn, "SELECT category, card FROM users WHERE id = '$firstId'");
            if ($uRes && $uRow = mysqli_fetch_assoc($uRes)) {
                if (empty($catVal) && !empty($uRow['category'])) $catVal = $uRow['category'];
                if (empty($cardVal) && !empty($uRow['card'])) $cardVal = $uRow['card'];
            }
        }
    }

    if (empty($cardVal)) $cardVal = 'Diamond';
    if (empty($catVal)) $catVal = 'B';

    $firstChar = strtoupper(substr($cardVal, 0, 1));
    $secondChar = strtoupper(substr($catVal, 0, 1));
    $prefix = $firstChar . $secondChar;

    $cntRes = mysqli_query($conn, "SELECT task_code FROM admin_tasks WHERE task_code LIKE '{$prefix}%'");
    $maxNum = 0;
    if ($cntRes) {
        while ($cRow = mysqli_fetch_assoc($cntRes)) {
            if (!empty($cRow['task_code'])) {
                $numPart = intval(substr($cRow['task_code'], strlen($prefix)));
                if ($numPart > $maxNum) $maxNum = $numPart;
            }
        }
    }
    return $prefix . ($maxNum + 1);
}

// Backfill missing task_code for existing tasks
$unassigned = mysqli_query($conn, "SELECT id, card, category, specific_member_id FROM admin_tasks WHERE task_code IS NULL OR task_code = '' ORDER BY id ASC");
if ($unassigned && mysqli_num_rows($unassigned) > 0) {
    while ($taskRow = mysqli_fetch_assoc($unassigned)) {
        $tId = $taskRow['id'];
        $c = $taskRow['card'] ?? '';
        $cat = $taskRow['category'] ?? '';
        $specId = $taskRow['specific_member_id'] ?? null;
        
        $cardVal = !empty($c) ? $c : 'Diamond';
        $catVal = !empty($cat) ? $cat : 'B';
        
        if ($specId && (empty($c) || empty($cat))) {
            $specIdEsc = intval($specId);
            $uRes = mysqli_query($conn, "SELECT category FROM users WHERE id = '$specIdEsc'");
            if ($uRes && $uRow = mysqli_fetch_assoc($uRes)) {
                if (empty($cat) && !empty($uRow['category'])) $catVal = $uRow['category'];
            }
        }
        
        $firstChar = strtoupper(substr(trim($cardVal), 0, 1));
        $secondChar = strtoupper(substr(trim($catVal), 0, 1));
        $prefix = $firstChar . $secondChar;
        
        $cntRes = mysqli_query($conn, "SELECT task_code FROM admin_tasks WHERE task_code LIKE '{$prefix}%' AND id != '$tId'");
        $maxNum = 0;
        if ($cntRes) {
            while ($cRow = mysqli_fetch_assoc($cntRes)) {
                if (!empty($cRow['task_code'])) {
                    $numPart = intval(substr($cRow['task_code'], strlen($prefix)));
                    if ($numPart > $maxNum) $maxNum = $numPart;
                }
            }
        }
        $newCode = $prefix . ($maxNum + 1);
        mysqli_query($conn, "UPDATE admin_tasks SET task_code = '$newCode' WHERE id = '$tId'");
    }
}

// Auto-create user_task_submissions table if it doesn't exist
$createSubmissionsTableSql = "CREATE TABLE IF NOT EXISTS `user_task_submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `task_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `submission_notes` TEXT NULL,
  `document_path` VARCHAR(255) NULL,
  `status` VARCHAR(50) DEFAULT 'Pending',
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_task` (`task_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $createSubmissionsTableSql);

// Fetch all users for "Assign to Specific Member" list and map
$allUsersList = [];
$userNamesMap = [];
$usersRes = mysqli_query($conn, "SELECT id, firstName, lastName, username, role, card, category FROM users ORDER BY firstName ASC");
if ($usersRes) {
    while ($uRow = mysqli_fetch_assoc($usersRes)) {
        $allUsersList[] = $uRow;
        $uCard = !empty($uRow['card']) ? $uRow['card'] : 'Diamond';
        $uCat = !empty($uRow['category']) ? $uRow['category'] : 'B';
        $userNamesMap[$uRow['id']] = trim($uRow['firstName'] . ' ' . $uRow['lastName']) . ' (' . $uCard . ' ' . $uCat . ')';
    }
}

// Seed default data if master table is empty
$checkEmpty = mysqli_query($conn, "SELECT id FROM admin_tasks");
if (mysqli_num_rows($checkEmpty) == 0) {
    $seedSql = "INSERT INTO admin_tasks (task_code, task_name, card, category, description, specifics, created_by) VALUES
    ('DB1', 'Surah Anfal ayat 50', 'Diamond', 'B', 'Recitation of Quran with tajweed and translation', 'Recite at least 1 Juz per week with proper rules', 'admin admin'),
    ('DB2', 'Weekly Study Notes', 'Diamond', 'B', 'Complete the weekly assigned task and study notes', 'Submit detailed study notes before Friday', 'admin admin'),
    ('GA1', 'Daily Namaz Log', 'Gold', 'A', 'Daily Namaz attendance and tracking log', 'Mark all 5 daily prayers in system', 'admin admin')";
    mysqli_query($conn, $seedSql);
}

// Handle Form Submissions
$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1️⃣ Admin Add Master Task
    if (isset($_POST['action']) && $_POST['action'] === 'add_task' && $canManageTasks) {
        $assignType = $_POST['assign_type'] ?? 'card_cat';
        
        if ($assignType === 'member') {
            $rawCard = null;
            $rawCategory = null;
            $card = "NULL";
            $category = "NULL";
            
            $memberIdsInput = $_POST['specific_member_ids'] ?? ($_POST['specific_member_id'] ?? []);
            if (is_array($memberIdsInput)) {
                $cleanIds = array_filter(array_map('intval', $memberIdsInput));
            } else {
                $cleanIds = array_filter(array_map('intval', explode(',', (string)$memberIdsInput)));
            }

            if (empty($cleanIds)) {
                $message = "Please select at least one specific member.";
                $messageType = "warning";
                goto end_post_handling;
            }

            $rawMemberIds = implode(',', $cleanIds);
            $specificMemberId = "'" . mysqli_real_escape_string($conn, $rawMemberIds) . "'";
        } else {
            $rawCard = $_POST['card'] ?? 'Diamond';
            $rawCategory = $_POST['category'] ?? 'B';
            $card = "'" . mysqli_real_escape_string($conn, $rawCard) . "'";
            $category = "'" . mysqli_real_escape_string($conn, $rawCategory) . "'";
            $rawMemberIds = null;
            $specificMemberId = "NULL";
        }

        $taskName = mysqli_real_escape_string($conn, trim($_POST['task_name'] ?? ''));
        $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
        $specifics = mysqli_real_escape_string($conn, trim($_POST['specifics'] ?? ''));
        if (empty($taskName)) $taskName = $description;
        if (empty($description)) $description = $taskName;

        $rawExpiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $expiryDateSql = $rawExpiry ? "'" . mysqli_real_escape_string($conn, date('Y-m-d H:i:s', strtotime($rawExpiry))) . "'" : "NULL";
        $createdBy = !empty($loggedFirstName) ? htmlspecialchars($loggedFirstName . ' ' . $loggedLastName) : 'admin admin';

        $taskCode = generateTaskCode($conn, $rawCard, $rawCategory, $rawMemberIds);

        if (empty($_POST['expiry_date'])) {
            $message = "Expiry Date & Time is mandatory. Please select an expiry date.";
            $messageType = "warning";
        } elseif (!empty($taskName) || !empty($description)) {
            $insertSql = "INSERT INTO admin_tasks (task_code, task_name, card, category, specific_member_id, description, specifics, expiry_date, created_by) VALUES ('$taskCode', '$taskName', $card, $category, $specificMemberId, '$description', '$specifics', $expiryDateSql, '$createdBy')";
            if (mysqli_query($conn, $insertSql)) {
                $message = "Task [$taskCode] added successfully!";
                $messageType = "success";
            } else {
                $message = "Error adding task: " . mysqli_error($conn);
                $messageType = "danger";
            }
        } else {
            $message = "Task name or description cannot be empty.";
            $messageType = "warning";
        }
    } 
    // 2️⃣ Admin Edit Master Task
    elseif (isset($_POST['action']) && $_POST['action'] === 'edit_task' && $canManageTasks) {
        $taskId = intval($_POST['task_id']);
        $assignType = $_POST['assign_type'] ?? 'card_cat';

        if ($assignType === 'member') {
            $rawCard = null;
            $rawCategory = null;
            $card = "NULL";
            $category = "NULL";
            
            $memberIdsInput = $_POST['specific_member_ids'] ?? ($_POST['specific_member_id'] ?? []);
            if (is_array($memberIdsInput)) {
                $cleanIds = array_filter(array_map('intval', $memberIdsInput));
            } else {
                $cleanIds = array_filter(array_map('intval', explode(',', (string)$memberIdsInput)));
            }

            if (empty($cleanIds)) {
                $message = "Please select at least one specific member.";
                $messageType = "warning";
                goto end_post_handling;
            }

            $rawMemberIds = implode(',', $cleanIds);
            $specificMemberId = "'" . mysqli_real_escape_string($conn, $rawMemberIds) . "'";
        } else {
            $rawCard = $_POST['card'] ?? 'Diamond';
            $rawCategory = $_POST['category'] ?? 'B';
            $card = "'" . mysqli_real_escape_string($conn, $rawCard) . "'";
            $category = "'" . mysqli_real_escape_string($conn, $rawCategory) . "'";
            $rawMemberIds = null;
            $specificMemberId = "NULL";
        }

        $taskName = mysqli_real_escape_string($conn, trim($_POST['task_name'] ?? ''));
        $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
        $specifics = mysqli_real_escape_string($conn, trim($_POST['specifics'] ?? ''));
        if (empty($taskName)) $taskName = $description;
        if (empty($description)) $description = $taskName;

        $rawExpiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $expiryDateSql = $rawExpiry ? "'" . mysqli_real_escape_string($conn, date('Y-m-d H:i:s', strtotime($rawExpiry))) . "'" : "NULL";

        if (empty($_POST['expiry_date'])) {
            $message = "Expiry Date & Time is mandatory. Please select an expiry date.";
            $messageType = "warning";
        } else {
            // Check existing task code and recalculate if card/category changed or code missing
            $exRes = mysqli_query($conn, "SELECT card, category, specific_member_id, task_code FROM admin_tasks WHERE id = '$taskId'");
            $exRow = mysqli_fetch_assoc($exRes);
            $cardChanged = ($exRow && ($exRow['card'] !== $rawCard || $exRow['category'] !== $rawCategory));
            
            if (empty($exRow['task_code']) || $cardChanged) {
                $newTaskCode = generateTaskCode($conn, $rawCard, $rawCategory, $rawMemberIds);
                $codeSql = ", task_code = '$newTaskCode'";
            } else {
                $codeSql = "";
            }

            $updateSql = "UPDATE admin_tasks SET task_name = '$taskName', card = $card, category = $category, specific_member_id = $specificMemberId, description = '$description', specifics = '$specifics', expiry_date = $expiryDateSql $codeSql WHERE id = '$taskId'";
            if (mysqli_query($conn, $updateSql)) {
                $message = "Task updated successfully!";
                $messageType = "success";
            } else {
                $message = "Error updating task: " . mysqli_error($conn);
                $messageType = "danger";
            }
        }
    } 
    // 3️⃣ Admin Delete Master Task
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_task' && $canManageTasks) {
        $taskId = intval($_POST['task_id']);
        $deleteSql = "DELETE FROM admin_tasks WHERE id = '$taskId'";
        if (mysqli_query($conn, $deleteSql)) {
            mysqli_query($conn, "DELETE FROM user_task_submissions WHERE task_id = '$taskId'");
            $message = "Task deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error deleting task: " . mysqli_error($conn);
            $messageType = "danger";
        }
    }
    // 4️⃣ User Submit / Update Document & Task Submission
    elseif (isset($_POST['action']) && $_POST['action'] === 'user_submit_task') {
        $taskId = intval($_POST['task_id']);
        $notes = mysqli_real_escape_string($conn, $_POST['submission_notes'] ?? '');
        $docPath = "";

        // Verify task access permissions for regular members
        if (!$canManageTasks) {
            $cardEsc = mysqli_real_escape_string($conn, trim($loggedCard ?? ''));
            $catEsc = mysqli_real_escape_string($conn, trim($loggedCategory ?? ''));
            $verifyAccess = mysqli_query($conn, "SELECT id FROM admin_tasks WHERE id = '$taskId' AND (FIND_IN_SET('$loggedUserId', specific_member_id) > 0 OR ((specific_member_id IS NULL OR specific_member_id = '' OR specific_member_id = 'NULL') AND LOWER(TRIM(card)) = LOWER('$cardEsc') AND LOWER(TRIM(category)) = LOWER('$catEsc')))");
            if (!$verifyAccess || mysqli_num_rows($verifyAccess) == 0) {
                $message = "You are not authorized to submit to this task.";
                $messageType = "danger";
                goto end_post_handling;
            }
        }

        if (isset($_FILES['task_document']) && $_FILES['task_document']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['task_document'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp', 'txt'];

            if (in_array($fileExt, $allowedExts)) {
                $uploadDir = 'uploads/task_documents/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $filename = 'task_' . $taskId . '_user_' . $loggedUserId . '_' . time() . '.' . $fileExt;
                $destPath = $uploadDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $docPath = $destPath;
                }
            }
        }

        // Insert or Update submission
        $checkExisting = mysqli_query($conn, "SELECT document_path, status FROM user_task_submissions WHERE task_id = '$taskId' AND user_id = '$loggedUserId'");
        if ($checkExisting && mysqli_num_rows($checkExisting) > 0) {
            $existingRow = mysqli_fetch_assoc($checkExisting);
            if ($existingRow['status'] === 'Approved') {
                $message = "This task has already been approved and cannot be modified.";
                $messageType = "warning";
            } else {
                if (empty($docPath)) {
                    $docPath = $existingRow['document_path']; // preserve existing document if new file wasn't uploaded
                }
                $docSql = !empty($docPath) ? ", document_path = '$docPath'" : "";
                $updateSubSql = "UPDATE user_task_submissions SET submission_notes = '$notes' $docSql, status = 'Pending', submitted_at = NOW() WHERE task_id = '$taskId' AND user_id = '$loggedUserId'";
                if (mysqli_query($conn, $updateSubSql)) {
                    $message = "Task submission updated successfully! Pending admin approval.";
                    $messageType = "success";
                } else {
                    $message = "Error updating submission: " . mysqli_error($conn);
                    $messageType = "danger";
                }
            }
        } else {
            $insertSubSql = "INSERT INTO user_task_submissions (task_id, user_id, submission_notes, document_path, status, submitted_at) VALUES ('$taskId', '$loggedUserId', '$notes', '$docPath', 'Pending', NOW())";
            if (mysqli_query($conn, $insertSubSql)) {
                $message = "Task submitted successfully! Pending admin approval.";
                $messageType = "success";
            } else {
                $message = "Error submitting task: " . mysqli_error($conn);
                $messageType = "danger";
            }
        }
    }
    // 5️⃣ Admin Update User Submission Status (Approved / Pending)
    elseif (isset($_POST['action']) && $_POST['action'] === 'update_submission_status' && $canManageTasks) {
        $subId = intval($_POST['submission_id']);
        $newStatus = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Pending');

        $updateStatusSql = "UPDATE user_task_submissions SET status = '$newStatus' WHERE id = '$subId'";
        if (mysqli_query($conn, $updateStatusSql)) {
            $message = "Submission status updated to '$newStatus'!";
            $messageType = "success";
        } else {
            $message = "Error updating status: " . mysqli_error($conn);
            $messageType = "danger";
        }
    }
}
end_post_handling:

// Fetch Master Tasks with submission metrics & assigned member details
if ($canManageTasks) {
    $taskFilterSql = "";
} else {
    $cardEsc = mysqli_real_escape_string($conn, trim($loggedCard ?? ''));
    $catEsc = mysqli_real_escape_string($conn, trim($loggedCategory ?? ''));
    
    // Member only sees:
    // 1. Tasks explicitly assigned to this specific member (FIND_IN_SET)
    // 2. OR General tasks matching this member's Card & Category
    $taskFilterSql = "WHERE (FIND_IN_SET('$loggedUserId', t.specific_member_id) > 0) 
                      OR ((t.specific_member_id IS NULL OR t.specific_member_id = '' OR t.specific_member_id = 'NULL') 
                          AND LOWER(TRIM(t.card)) = LOWER('$cardEsc') 
                          AND LOWER(TRIM(t.category)) = LOWER('$catEsc'))";
}

$query = "SELECT t.*, 
          (SELECT COUNT(*) FROM user_task_submissions uts WHERE uts.task_id = t.id) as total_submissions,
          (SELECT COUNT(*) FROM user_task_submissions uts WHERE uts.task_id = t.id AND uts.status = 'Approved') as approved_submissions,
          (SELECT COUNT(*) FROM user_task_submissions uts WHERE uts.task_id = t.id AND uts.status = 'Pending') as pending_submissions,
          (SELECT COUNT(*) FROM user_task_submissions uts WHERE uts.task_id = t.id AND t.expiry_date IS NOT NULL AND uts.submitted_at > t.expiry_date) as late_submissions
          FROM admin_tasks t 
          $taskFilterSql
          ORDER BY t.id ASC";
$tasksResult = mysqli_query($conn, $query);

// Fetch Logged User Submissions map indexed by task_id
$userSubmissions = [];
if ($loggedUserId) {
    $userSubRes = mysqli_query($conn, "SELECT * FROM user_task_submissions WHERE user_id = '$loggedUserId'");
    if ($userSubRes) {
        while ($subRow = mysqli_fetch_assoc($userSubRes)) {
            $userSubmissions[$subRow['task_id']] = $subRow;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<?php include "header.php"; ?>

<style>
.admin-tasks-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}

.admin-tasks-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.1rem;
    font-weight: 700;
    color: #f8fafc;
    margin: 0;
}

.btn-add-task {
    background: #198754;
    color: #ffffff;
    font-weight: 600;
    font-size: 0.88rem;
    padding: 9px 20px;
    border-radius: 8px;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-add-task:hover {
    background: #157347;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
}

.filter-bar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.filter-bar .form-control,
.filter-bar .form-select {
    background: #101726;
    border: 1px solid #293647;
    color: #cbd5e1;
    font-size: 0.85rem;
    border-radius: 8px;
    padding: 8px 14px;
}

.filter-bar .form-control:focus,
.filter-bar .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.2);
    outline: none;
}

.admin-tasks-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.admin-tasks-table th {
    background: #101726;
    color: #94a3b8;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    padding: 14px 16px;
    border-bottom: 1px solid #293647;
    white-space: nowrap;
}

.admin-tasks-table td {
    padding: 14px 16px;
    font-size: 0.85rem;
    color: #cbd5e1;
    border-bottom: 1px solid #233042;
    vertical-align: middle;
    white-space: nowrap;
}

.admin-tasks-table td.task-name-cell {
    white-space: normal;
    min-width: 170px;
    max-width: 250px;
}

.admin-tasks-table td.task-desc-cell {
    white-space: normal;
    min-width: 200px;
    max-width: 320px;
}

.text-created-by {
    color: #e2e8f0 !important;
    font-weight: 500;
}

.badge-card {
    background: #1e293b;
    color: #38bdf8;
    border: 1px solid #334155;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 600;
}

.badge-category {
    background: rgba(139, 92, 246, 0.15);
    color: #a78bfa;
    border: 1px solid rgba(139, 92, 246, 0.3);
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.8rem;
}

.badge-frequency {
    background: rgba(13, 110, 253, 0.15);
    color: #60a5fa;
    border: 1px solid rgba(13, 110, 253, 0.3);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
}

.badge-status-pending {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
}

.badge-status-approved {
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.3);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
}

.badge-status-notstarted {
    background: rgba(148, 163, 184, 0.15);
    color: #94a3b8;
    border: 1px solid rgba(148, 163, 184, 0.3);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
}

.btn-edit-task {
    background: #d97706;
    color: #ffffff;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 5px 14px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: background 0.2s;
}

.btn-edit-task:hover {
    background: #b45309;
    color: #ffffff;
}

.btn-user-submit {
    background: #0284c7;
    color: #ffffff;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 6px 14px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: background 0.2s;
}

.btn-user-submit:hover {
    background: #0369a1;
    color: #ffffff;
}

.member-circle-check {
    width: 20px !important;
    height: 20px !important;
    border-radius: 50% !important;
    border: 2px solid #3b82f6 !important;
    background-color: #101726;
    cursor: pointer;
    margin: 0 !important;
    flex-shrink: 0;
    transition: all 0.2s ease;
}

.member-circle-check:checked {
    background-color: #3b82f6 !important;
    border-color: #3b82f6 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='m6 10 3 3 6-6'/%3e%3c/svg%3e") !important;
}

.member-check-item:hover {
    background: rgba(59, 130, 246, 0.1) !important;
}

.btn-view-submissions {
    background: #4f46e5;
    color: #ffffff;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 5px 14px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: background 0.2s;
}

.btn-view-submissions:hover {
    background: #4338ca;
    color: #ffffff;
}
</style>

<body>
    <?php include "auth.php"; ?>
    <?php include "sidebar.php"; ?>
    <?php include "mainTopBar.php"; ?>

    <div class="breadcome-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="breadcome-list single-page-breadcome">
                        <div class="row">
                            <div class="col-6">
                                <h6 class="mb-0" style="font-size:.9rem;color:#6c757d;">Task Management</h6>
                            </div>
                            <div class="col-6">
                                <ul class="breadcome-menu">
                                    <li><a href="dashboard.php">Home</a> <span class="bread-slash">/</span></li>
                                    <li><span class="bread-blod">Tasks</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Start -->
    <div class="static-table-area mg-t-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="sparkline12-list mg-b-15">
                        <div class="sparkline12-hd">
                            <!-- Header -->
                            <div class="admin-tasks-header">
                                <h5 class="admin-tasks-title">
                                    <i class="bi bi-clipboard-check text-success fs-5"></i>
                                    <span><?php echo $canManageTasks ? 'Manage Tasks' : 'My Tasks & Submissions'; ?></span>
                                </h5>
                                <?php if ($canManageTasks): ?>
                                <button type="button" class="btn-add-task" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                                    <i class="bi bi-plus-lg"></i> Add Task
                                </button>
                                <?php endif; ?>
                            </div>

                            <!-- Filters -->
                            <div class="filter-bar">
                                <div style="flex: 1; min-width: 200px;">
                                    <input type="text" id="searchInput" class="form-control" placeholder="Search tasks...">
                                </div>
                                <?php if ($canManageTasks): ?>
                                <div>
                                    <select id="cardFilter" class="form-select">
                                        <option value="">All Cards</option>
                                        <option value="Diamond">Diamond</option>
                                        <option value="Gold">Gold</option>
                                        <option value="Silver">Silver</option>
                                    </select>
                                </div>
                                <div>
                                    <select id="categoryFilter" class="form-select">
                                        <option value="">All Categories</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                    </select>
                                </div>
                                <?php else: ?>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge-card"><i class="bi bi-gem me-1"></i><?php echo htmlspecialchars($loggedCard ?: 'Diamond'); ?></span>
                                    <span class="badge-category"><?php echo htmlspecialchars($loggedCategory ?: 'B'); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="sparkline12-graph">
                            <!-- Table -->
                            <div class="table-responsive">
                                <table class="admin-tasks-table" id="tasksTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>CARD</th>
                                            <th>CATEGORY</th>
                                            <th>TASK NAME</th>
                                            <th>DESCRIPTION</th>
                                            <th>EXPIRY DATE</th>
                                            <th>STATUS</th>
                                            <th>ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($tasksResult) > 0): ?>
                                            <?php while ($row = mysqli_fetch_assoc($tasksResult)): ?>
                                                <?php 
                                                    $tId = $row['id'];
                                                    $taskCode = htmlspecialchars($row['task_code'] ?? ('T' . $tId));
                                                    $taskDisplayName = htmlspecialchars(!empty($row['task_name']) ? $row['task_name'] : $row['description']);
                                                    $sub = $userSubmissions[$tId] ?? null;
                                                    $subStatus = $sub['status'] ?? 'Not Started';

                                                    $totalSubs = intval($row['total_submissions']);
                                                    $approvedSubs = intval($row['approved_submissions']);
                                                    $pendingSubs = intval($row['pending_submissions']);
                                                    
                                                    $assignedText = '';
                                                    if (!empty($row['specific_member_id'])) {
                                                        $specIds = explode(',', $row['specific_member_id']);
                                                        $specNames = [];
                                                        foreach ($specIds as $sId) {
                                                            $sIdTrim = trim($sId);
                                                            if (!empty($sIdTrim) && isset($userNamesMap[$sIdTrim])) {
                                                                $specNames[] = $userNamesMap[$sIdTrim];
                                                            }
                                                        }
                                                        if (!empty($specNames)) {
                                                            $assignedText = implode(', ', $specNames);
                                                        }
                                                    }

                                                    $expiryRaw = $row['expiry_date'] ?? null;
                                                    $expiryFormatted = !empty($expiryRaw) ? date('d M Y, h:i A', strtotime($expiryRaw)) : '';
                                                    $expiryIso = !empty($expiryRaw) ? date('Y-m-d\TH:i', strtotime($expiryRaw)) : '';
                                                    $isExpired = (!empty($expiryRaw) && strtotime('now') > strtotime($expiryRaw));
                                                ?>
                                                <tr>
                                                    <td><span class="badge bg-primary font-weight-bold" style="font-size:0.82rem; letter-spacing:0.5px;"><?php echo $taskCode; ?></span></td>
                                                    <td>
                                                        <?php if (!empty($row['card'])): ?>
                                                            <span class="badge-card"><?php echo htmlspecialchars($row['card']); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted small">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($row['category'])): ?>
                                                            <span class="badge-category"><?php echo htmlspecialchars($row['category']); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted small">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="task-name-cell">
                                                        <span class="font-weight-bold text-white"><?php echo $taskDisplayName; ?></span>
                                                    </td>
                                                    <td class="task-desc-cell">
                                                        <div><?php echo htmlspecialchars($row['description']); ?></div>
                                                        <?php if (!empty($row['specifics'])): ?>
                                                            <small class="text-info d-block mt-1"><i class="bi bi-info-circle me-1"></i><?php echo htmlspecialchars($row['specifics']); ?></small>
                                                        <?php endif; ?>
                                                        <?php if (!empty($assignedText)): ?>
                                                            <small class="text-warning d-block mt-1"><i class="bi bi-people-fill me-1"></i>Assigned to: <?php echo htmlspecialchars($assignedText); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($expiryFormatted)): ?>
                                                            <span class="badge-frequency" style="<?php echo $isExpired ? 'background:rgba(239, 68, 68, 0.15); color:#f87171; border-color:rgba(239, 68, 68, 0.3);' : 'background:rgba(13, 110, 253, 0.15); color:#60a5fa; border-color:rgba(13, 110, 253, 0.3);'; ?>">
                                                                <i class="bi bi-clock me-1"></i><?php echo $expiryFormatted; ?>
                                                                <?php if ($isExpired): ?>
                                                                    <small class="ms-1 fw-bold">(Expired)</small>
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted small">No Expiry</span>
                                                        <?php endif; ?>
                                                    </td>

                                                    <!-- STATUS Column -->
                                                    <td>
                                                        <?php 
                                                            $lateSubs = intval($row['late_submissions'] ?? 0);
                                                            $isSubLate = (!empty($row['expiry_date']) && !empty($sub['submitted_at']) && strtotime($sub['submitted_at']) > strtotime($row['expiry_date']));
                                                        ?>
                                                        <?php if (!$canManageTasks): ?>
                                                            <!-- Member / Trainee Status -->
                                                            <?php if ($subStatus === 'Approved'): ?>
                                                                <span class="badge-status-approved"><i class="bi bi-check-circle-fill me-1"></i>Approved</span>
                                                            <?php elseif ($subStatus === 'Pending'): ?>
                                                                <span class="badge-status-pending"><i class="bi bi-hourglass-split me-1"></i>Pending Approval</span>
                                                            <?php else: ?>
                                                                <span class="badge-status-notstarted"><i class="bi bi-dash-circle me-1"></i>Not Started</span>
                                                            <?php endif; ?>
                                                            <?php if ($isSubLate): ?>
                                                                <div class="mt-1"><span class="badge bg-danger" style="font-size:0.7rem; font-weight:600; padding:2px 6px;"><i class="bi bi-clock-history me-1"></i>Late</span></div>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <!-- Admin / Committee / Representative Status Summary -->
                                                            <div class="d-flex flex-column gap-1 align-items-start">
                                                                <?php if ($pendingSubs > 0): ?>
                                                                    <span class="badge-status-pending"><i class="bi bi-hourglass-split me-1"></i>Pending (<?php echo $pendingSubs; ?>)</span>
                                                                <?php endif; ?>
                                                                <?php if ($approvedSubs > 0): ?>
                                                                    <span class="badge-status-approved"><i class="bi bi-check-circle-fill me-1"></i>Approved (<?php echo $approvedSubs; ?>)</span>
                                                                <?php endif; ?>
                                                                <?php if ($totalSubs == 0): ?>
                                                                    <span class="badge-status-notstarted"><i class="bi bi-dash-circle me-1"></i>No Submissions</span>
                                                                <?php endif; ?>
                                                                <?php if ($lateSubs > 0): ?>
                                                                    <div><span class="badge bg-danger" style="font-size:0.72rem; font-weight:600; padding:2px 8px;"><i class="bi bi-clock-history me-1"></i>Late (<?php echo $lateSubs; ?>)</span></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>

                                                    <td>
                                                        <?php if ($canManageTasks): ?>
                                                            <!-- Admin / Committee / Representative Actions -->
                                                            <button type="button" class="btn-edit-task me-1" 
                                                                data-id="<?php echo $row['id']; ?>"
                                                                data-task-code="<?php echo $taskCode; ?>"
                                                                data-task-name="<?php echo $taskDisplayName; ?>"
                                                                data-card="<?php echo htmlspecialchars($row['card'] ?? 'Diamond'); ?>"
                                                                data-category="<?php echo htmlspecialchars($row['category'] ?? 'B'); ?>"
                                                                data-member-ids="<?php echo htmlspecialchars($row['specific_member_id'] ?? ''); ?>"
                                                                data-description="<?php echo htmlspecialchars($row['description']); ?>"
                                                                data-specifics="<?php echo htmlspecialchars($row['specifics'] ?? ''); ?>"
                                                                data-expiry="<?php echo $expiryIso; ?>"
                                                                onclick="openEditModal(this)">
                                                                <i class="bi bi-pencil-fill"></i> Edit
                                                            </button>

                                                            <button type="button" class="btn-view-submissions"
                                                                data-id="<?php echo $row['id']; ?>"
                                                                data-task-code="<?php echo $taskCode; ?>"
                                                                data-task-name="<?php echo $taskDisplayName; ?>"
                                                                data-description="<?php echo htmlspecialchars($row['description']); ?>"
                                                                onclick="openSubmissionsModal(this)">
                                                                <i class="bi bi-folder-symlink-fill"></i> Submissions (<?php echo $totalSubs; ?>)
                                                            </button>
                                                        <?php else: ?>
                                                            <!-- Member / Trainee Actions according to Submission Status -->
                                                            <?php if ($subStatus === 'Approved'): ?>
                                                                <button type="button" class="btn-user-submit"
                                                                    style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); border-color:#059669; color:#fff;"
                                                                    data-id="<?php echo $row['id']; ?>"
                                                                    data-task-code="<?php echo $taskCode; ?>"
                                                                    data-task-name="<?php echo $taskDisplayName; ?>"
                                                                    data-description="<?php echo htmlspecialchars($row['description']); ?>"
                                                                    data-specifics="<?php echo htmlspecialchars($row['specifics'] ?? ''); ?>"
                                                                    data-expiry="<?php echo $expiryFormatted; ?>"
                                                                    data-is-expired="<?php echo $isExpired ? '1' : '0'; ?>"
                                                                    data-notes="<?php echo htmlspecialchars($sub['submission_notes'] ?? ''); ?>"
                                                                    data-doc="<?php echo htmlspecialchars($sub['document_path'] ?? ''); ?>"
                                                                    data-status="Approved"
                                                                    onclick="openUserSubmitModal(this)">
                                                                    <i class="bi bi-file-earmark-check"></i> View Document
                                                                </button>
                                                            <?php elseif ($subStatus === 'Pending'): ?>
                                                                <button type="button" class="btn-user-submit"
                                                                    style="background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%); border-color:#0284c7; color:#fff;"
                                                                    data-id="<?php echo $row['id']; ?>"
                                                                    data-task-code="<?php echo $taskCode; ?>"
                                                                    data-task-name="<?php echo $taskDisplayName; ?>"
                                                                    data-description="<?php echo htmlspecialchars($row['description']); ?>"
                                                                    data-specifics="<?php echo htmlspecialchars($row['specifics'] ?? ''); ?>"
                                                                    data-expiry="<?php echo $expiryFormatted; ?>"
                                                                    data-is-expired="<?php echo $isExpired ? '1' : '0'; ?>"
                                                                    data-notes="<?php echo htmlspecialchars($sub['submission_notes'] ?? ''); ?>"
                                                                    data-doc="<?php echo htmlspecialchars($sub['document_path'] ?? ''); ?>"
                                                                    data-status="Pending"
                                                                    onclick="openUserSubmitModal(this)">
                                                                    <i class="bi bi-pencil-square"></i> View / Edit Document
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="button" class="btn-user-submit"
                                                                    style="background: linear-gradient(135deg, #0d6efd 0%, #3b82f6 100%); border-color:#0d6efd; color:#fff;"
                                                                    data-id="<?php echo $row['id']; ?>"
                                                                    data-task-code="<?php echo $taskCode; ?>"
                                                                    data-task-name="<?php echo $taskDisplayName; ?>"
                                                                    data-description="<?php echo htmlspecialchars($row['description']); ?>"
                                                                    data-specifics="<?php echo htmlspecialchars($row['specifics'] ?? ''); ?>"
                                                                    data-expiry="<?php echo $expiryFormatted; ?>"
                                                                    data-is-expired="<?php echo $isExpired ? '1' : '0'; ?>"
                                                                    data-notes=""
                                                                    data-doc=""
                                                                    data-status="Not Started"
                                                                    onclick="openUserSubmitModal(this)">
                                                                    <i class="bi bi-upload"></i> Submit / Upload Document
                                                                </button>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">No tasks found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($canManageTasks): ?>
    <!-- ── ADMIN ADD TASK MODAL ── -->
    <div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background:#192436; border:1px solid #293647; color:#fff;">
                <form method="POST">
                    <div class="modal-header" style="border-bottom:1px solid #293647;">
                        <h5 class="modal-title text-white" id="addTaskModalLabel">
                            <i class="bi bi-plus-circle text-success me-2"></i>Add New Task
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_task">
                        
                        <!-- Section Selector Radio -->
                        <div class="mb-3 p-2 px-3" style="background:#101726; border-radius:8px; border:1px solid #293647;">
                            <label class="form-label text-white small font-weight-bold d-block mb-2">Assign Task To:</label>
                            <div class="form-check form-check-inline me-3">
                                <input class="form-check-input" type="radio" name="assign_type" id="add_assign_card_cat" value="card_cat" checked onchange="toggleAssignType('add')">
                                <label class="form-check-label text-white small" for="add_assign_card_cat">By Card & Category</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="assign_type" id="add_assign_member" value="member" onchange="toggleAssignType('add')">
                                <label class="form-check-label text-white small" for="add_assign_member">Specific Member</label>
                            </div>
                        </div>

                        <!-- Section 1: Card & Category -->
                        <div id="add_card_cat_section" class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label text-white small font-weight-bold mb-1">Card</label>
                                <select name="card" class="form-select" style="background:#101726; border-color:#293647; color:#fff;">
                                    <option value="Diamond">Diamond</option>
                                    <option value="Gold">Gold</option>
                                    <option value="Silver">Silver</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label text-white small font-weight-bold mb-1">Category</label>
                                <select name="category" class="form-select" style="background:#101726; border-color:#293647; color:#fff;">
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                        </div>

                        <!-- Section 2: Specific Members (Circular Checkboxes) -->
                        <div id="add_member_section" class="mb-3" style="display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label text-white small font-weight-bold mb-0">Select Specific Members <span style="color:#dc3545;">*</span></label>
                                <span class="badge bg-secondary" id="add_selected_count" style="font-size:0.75rem;">0 selected</span>
                            </div>
                            
                            <div class="input-group input-group-sm mb-2 mt-1">
                                <span class="input-group-text" style="background:#101726; border-color:#293647; color:#6c7a8d;"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="add_member_search" placeholder="Filter members..." style="background:#101726; border-color:#293647; color:#fff;" onkeyup="filterMemberList('add')">
                                <button class="btn btn-outline-secondary btn-sm text-white" type="button" onclick="selectAllMembers('add', true)">All</button>
                                <button class="btn btn-outline-secondary btn-sm text-white" type="button" onclick="selectAllMembers('add', false)">Clear</button>
                            </div>

                            <div class="member-checkbox-list" id="add_member_list" style="max-height: 190px; overflow-y: auto; background:#101726; border: 1px solid #293647; border-radius: 8px; padding: 6px 8px;">
                                <?php foreach ($allUsersList as $u): 
                                    $uCard = !empty($u['card']) ? $u['card'] : 'Diamond';
                                    $uCat = !empty($u['category']) ? $u['category'] : 'B';
                                    $nameWithCardCat = htmlspecialchars($u['firstName'] . ' ' . $u['lastName']) . ' (' . htmlspecialchars($uCard . ' ' . $uCat) . ')';
                                ?>
                                    <label class="member-check-item d-flex align-items-center p-2 mb-1 rounded" style="cursor:pointer;">
                                        <input type="checkbox" name="specific_member_ids[]" value="<?php echo $u['id']; ?>" class="form-check-input member-circle-check me-2" onchange="updateSelectedCount('add')">
                                        <div class="d-flex flex-column" style="line-height:1.2;">
                                            <span class="text-white small font-weight-bold member-name-txt"><?php echo $nameWithCardCat; ?></span>
                                            <span class="text-muted" style="font-size:0.72rem;">@<?php echo htmlspecialchars($u['username']); ?> &bull; <?php echo ucfirst(htmlspecialchars($u['role'] ?? 'member')); ?></span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-white small font-weight-bold mb-1">Task Name *</label>
                            <input type="text" name="task_name" class="form-control" style="background:#101726; border-color:#293647; color:#fff;" required placeholder="e.g. Surah Anfal ayat 50">
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-white small font-weight-bold mb-1">Task Description / Instructions *</label>
                            <textarea name="description" class="form-control" rows="2" style="background:#101726; border-color:#293647; color:#fff;" required placeholder="Enter task instructions / main description..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-white small font-weight-bold mb-1">Specific Instructions / Details (Optional)</label>
                            <textarea name="specifics" class="form-control" rows="2" style="background:#101726; border-color:#293647; color:#fff;" placeholder="Additional specific details or guidelines for this task..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-white small font-weight-bold mb-1">Expiry Date & Time <span style="color:#dc3545;">*</span></label>
                            <input type="datetime-local" name="expiry_date" class="form-control" style="background:#101726; border-color:#293647; color:#fff;" required>
                            <small class="text-muted d-block mt-1">Set when this task expires. Submissions past this date will be tagged as Late.</small>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #293647;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success font-weight-bold">Save Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ── ADMIN EDIT TASK MODAL ── -->
    <div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background:#192436; border:1px solid #293647; color:#fff;">
                <form method="POST">
                    <div class="modal-header" style="border-bottom:1px solid #293647;">
                        <h5 class="modal-title text-white" id="editTaskModalLabel">
                            <i class="bi bi-pencil-square text-warning me-2"></i>Edit Task
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_task">
                        <input type="hidden" name="task_id" id="edit_task_id">
                        
                        <!-- Section Selector Radio -->
                        <div class="mb-3 p-2 px-3" style="background:#101726; border-radius:8px; border:1px solid #293647;">
                            <label class="form-label text-white small font-weight-bold d-block mb-2">Assign Task To:</label>
                            <div class="form-check form-check-inline me-3">
                                <input class="form-check-input" type="radio" name="assign_type" id="edit_assign_card_cat" value="card_cat" checked onchange="toggleAssignType('edit')">
                                <label class="form-check-label text-white small" for="edit_assign_card_cat">By Card & Category</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="assign_type" id="edit_assign_member" value="member" onchange="toggleAssignType('edit')">
                                <label class="form-check-label text-white small" for="edit_assign_member">Specific Member</label>
                            </div>
                        </div>

                        <!-- Section 1: Card & Category -->
                        <div id="edit_card_cat_section" class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label text-white small font-weight-bold mb-1">Card</label>
                                <select name="card" id="edit_card" class="form-select" style="background:#101726; border-color:#293647; color:#fff;">
                                    <option value="Diamond">Diamond</option>
                                    <option value="Gold">Gold</option>
                                    <option value="Silver">Silver</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label text-white small font-weight-bold mb-1">Category</label>
                                <select name="category" id="edit_category" class="form-select" style="background:#101726; border-color:#293647; color:#fff;">
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                        </div>

                        <!-- Section 2: Specific Members (Circular Checkboxes) -->
                        <div id="edit_member_section" class="mb-3" style="display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label text-white small font-weight-bold mb-0">Select Specific Members <span style="color:#dc3545;">*</span></label>
                                <span class="badge bg-secondary" id="edit_selected_count" style="font-size:0.75rem;">0 selected</span>
                            </div>
                            
                            <div class="input-group input-group-sm mb-2 mt-1">
                                <span class="input-group-text" style="background:#101726; border-color:#293647; color:#6c7a8d;"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="edit_member_search" placeholder="Search members..." style="background:#101726; border-color:#293647; color:#fff;" onkeyup="filterMemberList('edit')">
                                <button class="btn btn-outline-secondary btn-sm text-white" type="button" onclick="selectAllMembers('edit', true)">All</button>
                                <button class="btn btn-outline-secondary btn-sm text-white" type="button" onclick="selectAllMembers('edit', false)">Clear</button>
                            </div>

                            <div class="member-checkbox-list" id="edit_member_list" style="max-height: 190px; overflow-y: auto; background:#101726; border: 1px solid #293647; border-radius: 8px; padding: 6px 8px;">
                                <?php foreach ($allUsersList as $u): 
                                    $uCard = !empty($u['card']) ? $u['card'] : 'Diamond';
                                    $uCat = !empty($u['category']) ? $u['category'] : 'B';
                                    $nameWithCardCat = htmlspecialchars($u['firstName'] . ' ' . $u['lastName']) . ' (' . htmlspecialchars($uCard . ' ' . $uCat) . ')';
                                ?>
                                    <label class="member-check-item d-flex align-items-center p-2 mb-1 rounded" style="cursor:pointer;">
                                        <input type="checkbox" name="specific_member_ids[]" value="<?php echo $u['id']; ?>" class="form-check-input member-circle-check me-2" onchange="updateSelectedCount('edit')">
                                        <div class="d-flex flex-column" style="line-height:1.2;">
                                            <span class="text-white small font-weight-bold member-name-txt"><?php echo $nameWithCardCat; ?></span>
                                            <span class="text-muted" style="font-size:0.72rem;">@<?php echo htmlspecialchars($u['username']); ?> &bull; <?php echo ucfirst(htmlspecialchars($u['role'] ?? 'member')); ?></span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-white small font-weight-bold mb-1">Task Name *</label>
                            <input type="text" name="task_name" id="edit_task_name" class="form-control" style="background:#101726; border-color:#293647; color:#fff;" required placeholder="e.g. Surah Anfal ayat 50">
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-white small font-weight-bold mb-1">Task Description / Instructions *</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="2" style="background:#101726; border-color:#293647; color:#fff;" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-white small font-weight-bold mb-1">Specific Instructions / Details (Optional)</label>
                            <textarea name="specifics" id="edit_specifics" class="form-control" rows="2" style="background:#101726; border-color:#293647; color:#fff;"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-white small font-weight-bold mb-1">Expiry Date & Time <span style="color:#dc3545;">*</span></label>
                            <input type="datetime-local" name="expiry_date" id="edit_expiry_date" class="form-control" style="background:#101726; border-color:#293647; color:#fff;" required>
                            <small class="text-muted d-block mt-1">Set when this task expires. Submissions past this date will be tagged as Late.</small>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #293647; justify-content: space-between;">
                        <button type="submit" form="deleteTaskForm" class="btn btn-danger btn-sm">Delete Task</button>
                        <div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning text-white font-weight-bold">Update Task</button>
                        </div>
                    </div>
                </form>

                <!-- Hidden Delete Form -->
                <form id="deleteTaskForm" method="POST" onsubmit="return confirm('Are you sure you want to delete this task?');">
                    <input type="hidden" name="action" value="delete_task">
                    <input type="hidden" name="task_id" id="delete_task_id">
                </form>
            </div>
        </div>
    </div>

    <!-- ── ADMIN VIEW SUBMISSIONS MODAL ── -->
    <div class="modal fade" id="adminSubmissionsModal" tabindex="-1" aria-labelledby="adminSubmissionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content" style="background:#192436; border:1px solid #293647; color:#fff;">
                <div class="modal-header" style="border-bottom:1px solid #293647;">
                    <h5 class="modal-title text-white" id="adminSubmissionsModalLabel">
                        <i class="bi bi-folder-check text-info me-2"></i>User Submissions
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="adminSubmissionsAlert" class="alert py-2 px-3 mb-3" style="display:none; font-size:0.88rem;"></div>
                    <p class="text-muted small mb-3" id="adminTaskDescText"></p>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover table-bordered mb-0" style="font-size:0.85rem;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Dars Area</th>
                                    <th>Document</th>
                                    <th>Submission Notes</th>
                                    <th>Status</th>
                                    <th>Submitted At</th>
                                </tr>
                            </thead>
                            <tbody id="adminSubmissionsTableBody">
                                <!-- Injected via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between" style="border-top:1px solid #293647;">
                    <div id="adminSubmissionsCountInfo" class="small text-muted"></div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" id="saveSubmissionsBtn" class="btn btn-success font-weight-bold" onclick="saveSubmissionsStatus()">
                            <i class="bi bi-check-lg me-1"></i>Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── USER START / SUBMIT / VIEW TASK MODAL ── -->
    <div class="modal fade" id="userSubmitModal" tabindex="-1" aria-labelledby="userSubmitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background:#192436; border:1px solid #293647; color:#fff;">
                <form method="POST" enctype="multipart/form-data" id="userSubmitForm">
                    <div class="modal-header" style="border-bottom:1px solid #293647;">
                        <h5 class="modal-title text-white" id="userSubmitModalLabel">
                            <i class="bi bi-upload text-info me-2" id="userModalHeaderIcon"></i><span id="userModalHeaderTitle">Submit Task / Document</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="user_submit_task">
                        <input type="hidden" name="task_id" id="user_task_id">

                        <!-- Task Details Card -->
                        <div class="p-3 mb-3" style="background:#101726; border-radius:8px; border:1px solid #293647;">
                            <label class="text-white small font-weight-bold d-block mb-1">TASK DETAILS</label>
                            <div id="userTaskDescText" class="text-white font-weight-bold mb-2" style="font-size:0.92rem;"></div>
                            <div id="userTaskSpecificsText" class="text-info small" style="display:none;"></div>
                        </div>

                        <!-- Status Alert Banner -->
                        <div id="userStatusBanner" class="mb-3" style="display:none;"></div>

                        <!-- Uploaded File Alert -->
                        <div id="existingDocAlert" class="mb-3" style="display:none;">
                            <div class="p-2 px-3 d-flex justify-content-between align-items-center" style="background:rgba(2,132,199,0.15); border:1px solid rgba(2,132,199,0.3); border-radius:6px;">
                                <span class="small text-info"><i class="bi bi-file-earmark-check me-1"></i>Uploaded File:</span>
                                <a id="existingDocLink" href="#" target="_blank" class="btn btn-sm btn-outline-info p-1 px-2 text-decoration-none small">
                                    <i class="bi bi-eye"></i> View Document
                                </a>
                            </div>
                        </div>

                        <!-- Attach Document section (hidden when Approved) -->
                        <div class="mb-3" id="docUploadGroup">
                            <label class="form-label text-white small font-weight-bold mb-1" id="docUploadLabel">Attach Document (PDF, DOCX, Image)</label>
                            <input type="file" name="task_document" id="user_task_document" class="form-control" style="background:#101726; border-color:#293647; color:#fff;" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,.txt">
                            <small class="text-muted" id="docUploadHelp">Upload your work file (PDF, DOCX, Image, Text).</small>
                        </div>

                        <!-- Notes section (readonly when Approved) -->
                        <div class="mb-3">
                            <label class="form-label text-white small font-weight-bold mb-1" id="notesLabel">Submission Notes / Details</label>
                            <textarea name="submission_notes" id="user_submission_notes" class="form-control" rows="3" style="background:#101726; border-color:#293647; color:#fff;" placeholder="Add any comments or notes about your work..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #293647;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="userSubmitBtn" class="btn btn-info text-white font-weight-bold">Submit Document</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Hidden Form for Admin Updating Status -->
    <form id="updateStatusForm" method="POST" style="display:none;">
        <input type="hidden" name="action" value="update_submission_status">
        <input type="hidden" name="submission_id" id="status_sub_id">
        <input type="hidden" name="status" id="status_new_val">
    </form>

    <script>
    function toggleAssignType(prefix) {
        var isMember = document.getElementById(prefix + '_assign_member').checked;
        var cardCatSec = document.getElementById(prefix + '_card_cat_section');
        var memberSec = document.getElementById(prefix + '_member_section');

        if (isMember) {
            cardCatSec.style.display = 'none';
            memberSec.style.display = 'block';
        } else {
            cardCatSec.style.display = 'flex';
            memberSec.style.display = 'none';
        }
    }

    function updateSelectedCount(prefix) {
        var count = document.querySelectorAll('#' + prefix + '_member_list input[type="checkbox"]:checked').length;
        var badge = document.getElementById(prefix + '_selected_count');
        if (badge) {
            badge.textContent = count + ' selected';
            badge.className = count > 0 ? 'badge bg-success' : 'badge bg-secondary';
        }
    }

    function filterMemberList(prefix) {
        var searchInput = document.getElementById(prefix + '_member_search');
        var search = (searchInput ? searchInput.value : '').toLowerCase();
        var items = document.querySelectorAll('#' + prefix + '_member_list .member-check-item');
        items.forEach(function(item) {
            var text = item.textContent.toLowerCase();
            item.style.display = text.includes(search) ? 'flex' : 'none';
        });
    }

    function selectAllMembers(prefix, isSelectAll) {
        var items = document.querySelectorAll('#' + prefix + '_member_list .member-check-item');
        items.forEach(function(item) {
            if (item.style.display !== 'none') {
                var cb = item.querySelector('input[type="checkbox"]');
                if (cb) cb.checked = isSelectAll;
            }
        });
        updateSelectedCount(prefix);
    }

    function openEditModal(btn) {
        var id = btn.getAttribute('data-id');
        var card = btn.getAttribute('data-card');
        var category = btn.getAttribute('data-category');
        var memberIdsStr = btn.getAttribute('data-member-ids') || '';
        var taskName = btn.getAttribute('data-task-name');
        var description = btn.getAttribute('data-description');
        var specifics = btn.getAttribute('data-specifics');
        var expiry = btn.getAttribute('data-expiry');

        document.getElementById('edit_task_id').value = id;
        document.getElementById('delete_task_id').value = id;

        var memberIds = memberIdsStr ? memberIdsStr.split(',').map(s => s.trim()).filter(Boolean) : [];

        if (memberIds.length > 0) {
            document.getElementById('edit_assign_member').checked = true;
            document.querySelectorAll('#edit_member_list input[type="checkbox"]').forEach(function(cb) {
                cb.checked = memberIds.includes(cb.value);
            });
            updateSelectedCount('edit');
            toggleAssignType('edit');
        } else {
            document.getElementById('edit_assign_card_cat').checked = true;
            document.querySelectorAll('#edit_member_list input[type="checkbox"]').forEach(function(cb) {
                cb.checked = false;
            });
            updateSelectedCount('edit');
            document.getElementById('edit_card').value = card || 'Diamond';
            document.getElementById('edit_category').value = category || 'B';
            toggleAssignType('edit');
        }

        document.getElementById('edit_task_name').value = taskName || description || '';
        document.getElementById('edit_description').value = description || '';
        document.getElementById('edit_specifics').value = specifics || '';
        document.getElementById('edit_expiry_date').value = expiry || '';

        var editModal = new bootstrap.Modal(document.getElementById('editTaskModal'));
        editModal.show();
    }

    function openUserSubmitModal(btn) {
        var id = btn.getAttribute('data-id');
        var taskCode = btn.getAttribute('data-task-code');
        var taskName = btn.getAttribute('data-task-name');
        var desc = btn.getAttribute('data-description');
        var specs = btn.getAttribute('data-specifics');
        var expiryText = btn.getAttribute('data-expiry');
        var isExpired = btn.getAttribute('data-is-expired');
        var notes = btn.getAttribute('data-notes');
        var doc = btn.getAttribute('data-doc');
        var status = btn.getAttribute('data-status') || 'Not Started';

        document.getElementById('user_task_id').value = id;
        
        var headerHtml = (taskCode ? '<span class="badge bg-primary me-2">' + taskCode + '</span>' : '') + '<span class="text-info font-weight-bold">' + (taskName || desc) + '</span>';
        if (desc && desc !== taskName) {
            headerHtml += '<div class="text-white mt-1 small font-weight-normal">' + desc + '</div>';
        }
        if (expiryText) {
            headerHtml += '<br><small class="text-muted font-weight-normal mt-1 d-inline-block"><i class="bi bi-clock me-1"></i>Expiry: ' + expiryText + '</small>';
        }
        if (isExpired === '1') {
            headerHtml += ' <span class="badge bg-warning text-dark ms-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Late Submission</span>';
        }
        document.getElementById('userTaskDescText').innerHTML = headerHtml;
        
        var specsBox = document.getElementById('userTaskSpecificsText');
        if (specs && specs.trim() !== '') {
            specsBox.style.display = 'block';
            specsBox.innerHTML = '<i class="bi bi-info-circle me-1"></i>Instructions: ' + specs;
        } else {
            specsBox.style.display = 'none';
            specsBox.innerHTML = '';
        }

        var notesField = document.getElementById('user_submission_notes');
        notesField.value = notes || '';

        var alertBox = document.getElementById('existingDocAlert');
        var docLink = document.getElementById('existingDocLink');
        var statusBanner = document.getElementById('userStatusBanner');
        var docUploadGroup = document.getElementById('docUploadGroup');
        var userSubmitBtn = document.getElementById('userSubmitBtn');
        var headerIcon = document.getElementById('userModalHeaderIcon');
        var headerTitle = document.getElementById('userModalHeaderTitle');
        var docUploadHelp = document.getElementById('docUploadHelp');

        if (doc && doc.trim() !== '') {
            alertBox.style.display = 'block';
            docLink.href = 'viewDocument.php?file=' + encodeURIComponent(doc);
        } else {
            alertBox.style.display = 'none';
            docLink.href = '#';
        }

        if (status === 'Approved') {
            // Case 3: APPROVED - User can ONLY VIEW
            headerIcon.className = 'bi bi-file-earmark-check text-success me-2';
            headerTitle.textContent = 'Approved Task Submission';
            statusBanner.style.display = 'block';
            statusBanner.innerHTML = '<div class="p-2 px-3" style="background:rgba(16,185,129,0.15); border:1px solid rgba(16,185,129,0.3); border-radius:6px; color:#34d399; font-size:0.85rem;"><i class="bi bi-check-circle-fill me-2"></i><strong>Submission Approved!</strong> This task submission is approved and locked.</div>';
            docUploadGroup.style.display = 'none';
            notesField.readOnly = true;
            notesField.style.background = '#0d131f';
            userSubmitBtn.style.display = 'none';
        } else if (status === 'Pending') {
            // Case 2: PENDING APPROVAL - User can VIEW or EDIT
            headerIcon.className = 'bi bi-pencil-square text-warning me-2';
            headerTitle.textContent = 'View / Edit Task Submission';
            statusBanner.style.display = 'block';
            statusBanner.innerHTML = '<div class="p-2 px-3" style="background:rgba(234,179,8,0.15); border:1px solid rgba(234,179,8,0.3); border-radius:6px; color:#fbbf24; font-size:0.85rem;"><i class="bi bi-hourglass-split me-2"></i><strong>Pending Approval.</strong> You can edit your notes or upload a new file below.</div>';
            docUploadGroup.style.display = 'block';
            docUploadHelp.textContent = doc ? 'Upload a new file to replace existing document, or leave blank to keep current file.' : 'Attach your document (PDF, DOCX, Image)';
            notesField.readOnly = false;
            notesField.style.background = '#101726';
            userSubmitBtn.style.display = 'inline-block';
            userSubmitBtn.className = 'btn btn-warning text-white font-weight-bold';
            userSubmitBtn.textContent = 'Update Submission';
        } else {
            // Case 1: NOT SUBMITTED - User can SUBMIT / UPLOAD
            headerIcon.className = 'bi bi-upload text-info me-2';
            headerTitle.textContent = 'Submit Task / Document';
            statusBanner.style.display = 'none';
            statusBanner.innerHTML = '';
            docUploadGroup.style.display = 'block';
            docUploadHelp.textContent = 'Upload your work file (PDF, DOCX, Image, Text).';
            notesField.readOnly = false;
            notesField.style.background = '#101726';
            userSubmitBtn.style.display = 'inline-block';
            userSubmitBtn.className = 'btn btn-primary font-weight-bold';
            userSubmitBtn.textContent = 'Submit Document';
        }

        var submitModal = new bootstrap.Modal(document.getElementById('userSubmitModal'));
        submitModal.show();
    }

    var currentSubmissionsTaskId = 0;
    var hasSubmissionsChanged = false;

    function openSubmissionsModal(btn) {
        var taskId = btn.getAttribute('data-id');
        var taskCode = btn.getAttribute('data-task-code');
        var taskName = btn.getAttribute('data-task-name');
        var desc = btn.getAttribute('data-description');

        currentSubmissionsTaskId = taskId;
        hasSubmissionsChanged = false;

        var alertBox = document.getElementById('adminSubmissionsAlert');
        if (alertBox) {
            alertBox.style.display = 'none';
            alertBox.innerHTML = '';
        }

        var saveBtn = document.getElementById('saveSubmissionsBtn');
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Save Changes';
            saveBtn.style.display = 'inline-block';
        }

        var countInfo = document.getElementById('adminSubmissionsCountInfo');
        if (countInfo) countInfo.textContent = '';

        var titleStr = (taskCode ? '<strong class="text-info me-2">[' + taskCode + ']</strong>' : '') + '<strong>' + (taskName || desc) + '</strong>';
        if (desc && desc !== taskName) {
            titleStr += ' — ' + desc;
        }
        document.getElementById('adminTaskDescText').innerHTML = titleStr;
        var tableBody = document.getElementById('adminSubmissionsTableBody');
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-3">Loading submissions...</td></tr>';

        var adminModal = new bootstrap.Modal(document.getElementById('adminSubmissionsModal'));
        adminModal.show();

        fetch('fetchTaskSubmissions.php?task_id=' + taskId)
            .then(res => res.json())
            .then(data => {
                tableBody.innerHTML = '';
                if (!data || data.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">No user submissions yet for this task.</td></tr>';
                    if (saveBtn) saveBtn.style.display = 'none';
                    return;
                }

                if (countInfo) {
                    countInfo.textContent = data.length + ' submission' + (data.length > 1 ? 's' : '');
                }

                data.forEach((sub, idx) => {
                    var docCell = sub.document_path 
                        ? `<a href="viewDocument.php?file=${encodeURIComponent(sub.document_path)}" target="_blank" class="btn btn-sm btn-outline-info p-1 px-2 text-decoration-none"><i class="bi bi-eye me-1"></i>View Document</a>`
                        : '<span class="text-muted">No File</span>';

                    var statusSelect = `
                        <select class="form-select form-select-sm submission-status-select" data-sub-id="${sub.id}" style="background:#101726; color:#fff; border-color:#293647; width:125px;">
                            <option value="Pending" ${sub.status === 'Pending' ? 'selected' : ''}>Pending</option>
                            <option value="Approved" ${sub.status === 'Approved' ? 'selected' : ''}>Approved</option>
                        </select>`;

                    var lateBadge = sub.is_late 
                        ? '<span class="badge bg-danger ms-1" style="font-size:0.72rem;" title="Submitted after expiry date"><i class="bi bi-clock-history me-1"></i>Late</span>' 
                        : '';

                    var tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${idx + 1}</td>
                        <td class="font-weight-bold text-white">${sub.firstName} ${sub.lastName} ${lateBadge}</td>
                        <td>${sub.area || '-'}</td>
                        <td>${docCell}</td>
                        <td>${sub.submission_notes || '-'}</td>
                        <td>${statusSelect}</td>
                        <td class="text-muted small">${sub.submitted_at}</td>
                    `;
                    tableBody.appendChild(tr);
                });
            })
            .catch(err => {
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">Error loading submissions.</td></tr>';
                if (saveBtn) saveBtn.style.display = 'none';
            });
    }

    function saveSubmissionsStatus() {
        var selects = document.querySelectorAll('#adminSubmissionsTableBody .submission-status-select');
        var alertBox = document.getElementById('adminSubmissionsAlert');
        var saveBtn = document.getElementById('saveSubmissionsBtn');

        if (!selects || selects.length === 0) {
            if (alertBox) {
                alertBox.className = 'alert alert-warning py-2 px-3 mb-3';
                alertBox.innerHTML = '<i class="bi bi-info-circle me-1"></i>No submissions to save.';
                alertBox.style.display = 'block';
            }
            return;
        }

        var items = [];
        selects.forEach(function(sel) {
            var subId = parseInt(sel.getAttribute('data-sub-id'), 10);
            var statusVal = sel.value;
            if (subId > 0) {
                items.push({ id: subId, status: statusVal });
            }
        });

        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Saving...';
        }

        fetch('updateSubmissionsStatus.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                task_id: currentSubmissionsTaskId,
                submissions: items
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.success) {
                if (alertBox) {
                    alertBox.className = 'alert alert-success py-2 px-3 mb-3';
                    alertBox.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>' + (data.message || 'Submissions saved successfully!');
                    alertBox.style.display = 'block';
                }
                hasSubmissionsChanged = true;
                if (saveBtn) {
                    saveBtn.className = 'btn btn-success font-weight-bold';
                    saveBtn.innerHTML = '<i class="bi bi-check2-all me-1"></i>Saved!';
                    setTimeout(function() {
                        saveBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Save Changes';
                        saveBtn.disabled = false;
                    }, 1500);
                }
            } else {
                if (alertBox) {
                    alertBox.className = 'alert alert-danger py-2 px-3 mb-3';
                    alertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>' + ((data && data.message) ? data.message : 'Error updating submissions.');
                    alertBox.style.display = 'block';
                }
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Save Changes';
                }
            }
        })
        .catch(err => {
            if (alertBox) {
                alertBox.className = 'alert alert-danger py-2 px-3 mb-3';
                alertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Network or server error: ' + err.message;
                alertBox.style.display = 'block';
            }
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Save Changes';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var submissionsModalEl = document.getElementById('adminSubmissionsModal');
        if (submissionsModalEl) {
            submissionsModalEl.addEventListener('hidden.bs.modal', function () {
                if (hasSubmissionsChanged) {
                    location.reload();
                }
            });
        }
    });

    // Client-side search and filtering
    document.addEventListener('DOMContentLoaded', function () {
        var searchInput = document.getElementById('searchInput');
        var cardFilter = document.getElementById('cardFilter');
        var categoryFilter = document.getElementById('categoryFilter');
        var tableRows = document.querySelectorAll('#tasksTable tbody tr');

        function filterTable() {
            var searchVal = searchInput ? searchInput.value.toLowerCase() : '';
            var cardVal = cardFilter ? cardFilter.value.toLowerCase() : '';
            var catVal = categoryFilter ? categoryFilter.value.toLowerCase() : '';

            tableRows.forEach(function (row) {
                var name = row.querySelector('.task-name-cell') ? row.querySelector('.task-name-cell').textContent.toLowerCase() : '';
                var desc = row.querySelector('.task-desc-cell') ? row.querySelector('.task-desc-cell').textContent.toLowerCase() : '';
                var card = row.querySelector('.badge-card') ? row.querySelector('.badge-card').textContent.toLowerCase() : '';
                var cat = row.querySelector('.badge-category') ? row.querySelector('.badge-category').textContent.toLowerCase() : '';

                var matchesSearch = !searchVal || desc.includes(searchVal) || name.includes(searchVal);
                var matchesCard = !cardVal || card.includes(cardVal);
                var matchesCat = !catVal || cat.includes(catVal);

                if (matchesSearch && matchesCard && matchesCat) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        if (searchInput) searchInput.addEventListener('keyup', filterTable);
        if (cardFilter) cardFilter.addEventListener('change', filterTable);
        if (categoryFilter) categoryFilter.addEventListener('change', filterTable);
    });
    </script>

    <!-- Script dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>
    <?php include "footer.php"; ?>
</body>
</html>
