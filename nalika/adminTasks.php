<?php
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Karachi');
} else {
    @date_default_timezone_set('Asia/Karachi');
}
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

// Ensure columns have enough capacity for multiple codes, cards, categories, and members
@mysqli_query($conn, "ALTER TABLE `admin_tasks` MODIFY COLUMN `task_code` VARCHAR(255) NULL");
@mysqli_query($conn, "ALTER TABLE `admin_tasks` MODIFY COLUMN `card` VARCHAR(255) NULL");
@mysqli_query($conn, "ALTER TABLE `admin_tasks` MODIFY COLUMN `category` VARCHAR(255) NULL");
@mysqli_query($conn, "ALTER TABLE `admin_tasks` MODIFY COLUMN `specific_member_id` TEXT NULL");
@mysqli_query($conn, "ALTER TABLE `admin_tasks` ADD COLUMN `task_name` VARCHAR(255) NULL AFTER `task_code`");
@mysqli_query($conn, "ALTER TABLE `admin_tasks` ADD COLUMN `specifics` TEXT NULL AFTER `description`");
@mysqli_query($conn, "ALTER TABLE `admin_tasks` ADD COLUMN `expiry_date` DATETIME NULL AFTER `specifics`");

// Helper function to generate Task Code(s) (e.g. SB1, or multiple like SB1, DB1)
function generateTaskCode($conn, $card, $category, $specificMemberIds = null, $excludeTaskId = null) {
    $excludeSql = $excludeTaskId ? "AND id != " . intval($excludeTaskId) : "";

    // If specific members are selected, generate code for EACH unique card+category combination
    if (!empty($specificMemberIds)) {
        if (is_array($specificMemberIds)) {
            $cleanIds = array_filter(array_map('intval', $specificMemberIds));
        } else {
            $cleanIds = array_filter(array_map('intval', explode(',', (string)$specificMemberIds)));
        }

        if (!empty($cleanIds)) {
            $idStr = implode(',', $cleanIds);
            $uRes = mysqli_query($conn, "SELECT id, card, category FROM users WHERE id IN ($idStr)");
            $prefixes = [];
            if ($uRes) {
                while ($u = mysqli_fetch_assoc($uRes)) {
                    $uC = !empty($u['card']) ? trim($u['card']) : 'Diamond';
                    $uCat = !empty($u['category']) ? trim($u['category']) : 'B';
                    $p = strtoupper(substr($uC, 0, 1)) . strtoupper(substr($uCat, 0, 1));
                    $prefixes[$p] = true;
                }
            }

            if (!empty($prefixes)) {
                $codes = [];
                $sortedPrefixes = array_keys($prefixes);
                sort($sortedPrefixes);
                foreach ($sortedPrefixes as $p) {
                    $cntRes = mysqli_query($conn, "SELECT task_code FROM admin_tasks WHERE task_code LIKE '%{$p}%' $excludeSql");
                    $maxNum = 0;
                    if ($cntRes) {
                        while ($cRow = mysqli_fetch_assoc($cntRes)) {
                            preg_match_all('/' . $p . '(\d+)/i', $cRow['task_code'] ?? '', $matches);
                            if (!empty($matches[1])) {
                                foreach ($matches[1] as $np) {
                                    $numPart = intval($np);
                                    if ($numPart > $maxNum) $maxNum = $numPart;
                                }
                            }
                        }
                    }
                    $codes[] = $p . ($maxNum + 1);
                }
                return implode(', ', $codes);
            }
        }
    }

    // Default By Card & Category
    $cardVal = !empty($card) ? trim($card) : 'Diamond';
    $catVal = !empty($category) ? trim($category) : 'B';

    $firstChar = strtoupper(substr($cardVal, 0, 1));
    $secondChar = strtoupper(substr($catVal, 0, 1));
    $prefix = $firstChar . $secondChar;

    $cntRes = mysqli_query($conn, "SELECT task_code FROM admin_tasks WHERE task_code LIKE '%{$prefix}%' $excludeSql");
    $maxNum = 0;
    if ($cntRes) {
        while ($cRow = mysqli_fetch_assoc($cntRes)) {
            preg_match_all('/' . $prefix . '(\d+)/i', $cRow['task_code'] ?? '', $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $np) {
                    $numPart = intval($np);
                    if ($numPart > $maxNum) $maxNum = $numPart;
                }
            }
        }
    }
    return $prefix . ($maxNum + 1);
}

// Backfill missing task_code and repair rows with missing cards / categories
$allTasksToRepair = mysqli_query($conn, "SELECT id, card, category, specific_member_id, task_code FROM admin_tasks WHERE task_code IS NULL OR task_code = '' OR card IS NULL OR card = '' OR card = '—' OR category IS NULL OR category = '' OR category = '—'");
if ($allTasksToRepair && mysqli_num_rows($allTasksToRepair) > 0) {
    while ($r = mysqli_fetch_assoc($allTasksToRepair)) {
        $rId = $r['id'];
        $currCard = trim($r['card'] ?? '');
        $currCat = trim($r['category'] ?? '');
        
        $resolvedCards = [];
        $resolvedCats = [];
        $memberPrefixes = [];
        
        // 1. If currently valid, collect them
        if (!empty($currCard) && strtolower($currCard) !== 'null' && $currCard !== '—') {
            foreach (explode(',', $currCard) as $ci) {
                $ciTrim = trim($ci);
                if (!empty($ciTrim) && strtolower($ciTrim) !== 'null' && $ciTrim !== '—') {
                    $resolvedCards[$ciTrim] = true;
                }
            }
        }
        if (!empty($currCat) && strtolower($currCat) !== 'null' && $currCat !== '—') {
            foreach (explode(',', $currCat) as $cati) {
                $catiTrim = trim($cati);
                if (!empty($catiTrim) && strtolower($catiTrim) !== 'null' && $catiTrim !== '—') {
                    $resolvedCats[$catiTrim] = true;
                }
            }
        }
        
        // 2. From specific_member_id if available
        $mIds = array_filter(array_map('intval', explode(',', $r['specific_member_id'] ?? '')));
        if (!empty($mIds)) {
            $mIdStr = implode(',', $mIds);
            $uRes = mysqli_query($conn, "SELECT card, category FROM users WHERE id IN ($mIdStr)");
            if ($uRes) {
                while ($u = mysqli_fetch_assoc($uRes)) {
                    $uC = !empty($u['card']) ? trim($u['card']) : 'Diamond';
                    $uCat = !empty($u['category']) ? trim($u['category']) : 'B';
                    $resolvedCards[$uC] = true;
                    $resolvedCats[$uCat] = true;
                    $p = strtoupper(substr($uC, 0, 1)) . strtoupper(substr($uCat, 0, 1));
                    $memberPrefixes[$p] = true;
                }
            }
        }
        
        // 3. From task_code(s) (e.g. DB1, SD1, SB1) if still missing
        $tCodes = array_filter(array_map('trim', explode(',', $r['task_code'] ?? '')));
        foreach ($tCodes as $tc) {
            if (strlen($tc) >= 2) {
                $cLetter = strtoupper(substr($tc, 0, 1));
                $catLetter = strtoupper(substr($tc, 1, 1));
                $decodedCard = ($cLetter === 'D') ? 'Diamond' : (($cLetter === 'G') ? 'Gold' : (($cLetter === 'S') ? 'Silver' : null));
                if ($decodedCard) $resolvedCards[$decodedCard] = true;
                if (in_array($catLetter, ['A', 'B', 'C', 'D'])) $resolvedCats[$catLetter] = true;
            }
        }
        
        // Fallbacks if absolutely empty
        if (empty($resolvedCards)) $resolvedCards['Diamond'] = true;
        if (empty($resolvedCats)) $resolvedCats['B'] = true;
        
        $finalCardStr = implode(', ', array_keys($resolvedCards));
        $finalCatStr = implode(', ', array_keys($resolvedCats));
        
        // Only regenerate task_code if it was empty
        if (empty($tCodes)) {
            $prefixesToUse = !empty($memberPrefixes) ? array_keys($memberPrefixes) : [];
            if (empty($prefixesToUse)) {
                foreach (array_keys($resolvedCards) as $rc) {
                    foreach (array_keys($resolvedCats) as $rcat) {
                        $prefixesToUse[] = strtoupper(substr($rc, 0, 1)) . strtoupper(substr($rcat, 0, 1));
                    }
                }
            }
            $prefixesToUse = array_unique($prefixesToUse);
            sort($prefixesToUse);
            
            $finalCodes = [];
            foreach ($prefixesToUse as $p) {
                $cntRes = mysqli_query($conn, "SELECT task_code FROM admin_tasks WHERE task_code LIKE '%{$p}%' AND id != '$rId'");
                $maxNum = 0;
                if ($cntRes) {
                    while ($cRow = mysqli_fetch_assoc($cntRes)) {
                        preg_match_all('/' . $p . '(\d+)/i', $cRow['task_code'] ?? '', $matches);
                        if (!empty($matches[1])) {
                            foreach ($matches[1] as $np) {
                                $npVal = intval($np);
                                if ($npVal > $maxNum) $maxNum = $npVal;
                            }
                        }
                    }
                }
                $finalCodes[] = $p . ($maxNum + 1);
            }
            $finalCodeStr = implode(', ', $finalCodes);
        } else {
            $finalCodeStr = implode(', ', $tCodes);
        }
        
        if ($currCard !== $finalCardStr || $currCat !== $finalCatStr || ($r['task_code'] ?? '') !== $finalCodeStr) {
            $eCard = mysqli_real_escape_string($conn, $finalCardStr);
            $eCat = mysqli_real_escape_string($conn, $finalCatStr);
            $eCode = mysqli_real_escape_string($conn, $finalCodeStr);
            mysqli_query($conn, "UPDATE admin_tasks SET card = '$eCard', category = '$eCat', task_code = '$eCode' WHERE id = '$rId'");
        }
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

            // Determine cards & categories from selected members
            $mIdStr = implode(',', $cleanIds);
            $uRes = mysqli_query($conn, "SELECT id, card, category FROM users WHERE id IN ($mIdStr)");
            $assignedCards = [];
            $assignedCats = [];
            if ($uRes) {
                while ($uRow = mysqli_fetch_assoc($uRes)) {
                    $c = !empty($uRow['card']) ? trim($uRow['card']) : 'Diamond';
                    $cat = !empty($uRow['category']) ? trim($uRow['category']) : 'B';
                    $assignedCards[$c] = true;
                    $assignedCats[$cat] = true;
                }
            }
            $rawCard = implode(', ', array_keys($assignedCards));
            $rawCategory = implode(', ', array_keys($assignedCats));
            $card = "'" . mysqli_real_escape_string($conn, $rawCard) . "'";
            $category = "'" . mysqli_real_escape_string($conn, $rawCategory) . "'";
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

            // Determine cards & categories from selected members
            $mIdStr = implode(',', $cleanIds);
            $uRes = mysqli_query($conn, "SELECT id, card, category FROM users WHERE id IN ($mIdStr)");
            $assignedCards = [];
            $assignedCats = [];
            if ($uRes) {
                while ($uRow = mysqli_fetch_assoc($uRes)) {
                    $c = !empty($uRow['card']) ? trim($uRow['card']) : 'Diamond';
                    $cat = !empty($uRow['category']) ? trim($uRow['category']) : 'B';
                    $assignedCards[$c] = true;
                    $assignedCats[$cat] = true;
                }
            }
            $rawCard = implode(', ', array_keys($assignedCards));
            $rawCategory = implode(', ', array_keys($assignedCats));
            $card = "'" . mysqli_real_escape_string($conn, $rawCard) . "'";
            $category = "'" . mysqli_real_escape_string($conn, $rawCategory) . "'";
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
            $newTaskCode = generateTaskCode($conn, $rawCard, $rawCategory, $rawMemberIds, $taskId);
            $codeSql = ", task_code = '$newTaskCode'";

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
                                <div style="flex: 0 1 260px; min-width: 180px;">
                                    <input type="text" id="searchInput" class="form-control" placeholder="Search tasks...">
                                </div>
                                <div style="margin-left: auto;" class="d-flex gap-2 flex-wrap align-items-center">
                                    <div>
                                        <select id="timeFilter" class="form-select">
                                            <option value="">All Time</option>
                                            <option value="daily">Daily / Today</option>
                                            <option value="yesterday">Yesterday</option>
                                            <option value="last_3_days">Last 3 Days</option>
                                            <option value="weekly">This Week</option>
                                            <option value="previous_week">Previous Week</option>
                                            <option value="monthly">This Month</option>
                                            <option value="previous_month">Previous Month</option>
                                            <option value="yearly">Yearly</option>
                                        </select>
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
                                    <div>
                                        <button type="button" id="resetFiltersBtn" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" style="border-color:#293647; color:#cbd5e1; padding:7px 12px; border-radius:8px; white-space:nowrap;" onclick="resetAllFilters()" title="Reset all filters">
                                            <i class="bi bi-arrow-counterclockwise"></i> <span>Reset Filters</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Active Filters Strip -->
                            <div id="activeFiltersStrip" class="d-flex align-items-center gap-2 flex-wrap mb-3 px-1" style="display:none !important; font-size:0.83rem;">
                                <span class="text-muted"><i class="bi bi-funnel-fill text-primary me-1"></i>Active filters:</span>
                                <span id="activeFilterBadges" class="d-flex gap-2 flex-wrap align-items-center"></span>
                                <a href="javascript:void(0)" onclick="resetAllFilters()" class="text-danger small ms-1 font-weight-bold" style="text-decoration:none;">Clear all</a>
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
                                                    $expiryDateOnly = !empty($expiryRaw) ? date('Y-m-d', strtotime($expiryRaw)) : '';
                                                    $createdDateOnly = !empty($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : '';
                                                    $isExpired = (!empty($expiryRaw) && strtotime('now') > strtotime($expiryRaw));

                                                    // Robustly resolve cards and categories for this row
                                                    $rowCardsList = array_filter(array_map('trim', explode(',', $row['card'] ?? '')));
                                                    if (empty($rowCardsList) && !empty($row['task_code'])) {
                                                        foreach (explode(',', $row['task_code']) as $tc) {
                                                            $cl = strtoupper(substr(trim($tc), 0, 1));
                                                            $decC = ($cl === 'D') ? 'Diamond' : (($cl === 'G') ? 'Gold' : (($cl === 'S') ? 'Silver' : null));
                                                            if ($decC && !in_array($decC, $rowCardsList)) $rowCardsList[] = $decC;
                                                        }
                                                    }
                                                    if (empty($rowCardsList)) $rowCardsList = ['Diamond'];

                                                    $rowCatsList = array_filter(array_map('trim', explode(',', $row['category'] ?? '')));
                                                    if (empty($rowCatsList) && !empty($row['task_code'])) {
                                                        foreach (explode(',', $row['task_code']) as $tc) {
                                                            $catl = strtoupper(substr(trim($tc), 1, 1));
                                                            if (in_array($catl, ['A', 'B', 'C', 'D']) && !in_array($catl, $rowCatsList)) $rowCatsList[] = $catl;
                                                        }
                                                    }
                                                    if (empty($rowCatsList)) $rowCatsList = ['B'];

                                                    $dataCardAttr = htmlspecialchars(strtolower(implode(',', $rowCardsList)));
                                                    $dataCatAttr = htmlspecialchars(strtolower(implode(',', $rowCatsList)));
                                                ?>
                                                <tr class="task-row" 
                                                    data-expiry-date="<?php echo $expiryDateOnly; ?>" 
                                                    data-created-date="<?php echo $createdDateOnly; ?>"
                                                    data-card="<?php echo $dataCardAttr; ?>"
                                                    data-category="<?php echo $dataCatAttr; ?>">
                                                    <td>
                                                        <?php
                                                        $codes = array_filter(array_map('trim', explode(',', $row['task_code'] ?? '')));
                                                        if (empty($codes)) $codes = ['T' . $tId];
                                                        foreach ($codes as $cIdx => $cCode):
                                                            $badgeBg = ($cIdx % 2 === 0) ? 'bg-primary' : 'bg-info text-dark';
                                                        ?>
                                                            <span class="badge <?php echo $badgeBg; ?> font-weight-bold me-1 mb-1" style="font-size:0.82rem; letter-spacing:0.5px;"><?php echo htmlspecialchars($cCode); ?></span>
                                                        <?php endforeach; ?>
                                                    </td>
                                                    <td>
                                                        <?php foreach ($rowCardsList as $cItem): ?>
                                                            <span class="badge-card me-1 mb-1 d-inline-block"><?php echo htmlspecialchars($cItem); ?></span>
                                                        <?php endforeach; ?>
                                                    </td>
                                                    <td>
                                                        <?php foreach ($rowCatsList as $catItem): ?>
                                                            <span class="badge-category me-1 mb-1"><?php echo htmlspecialchars($catItem); ?></span>
                                                        <?php endforeach; ?>
                                                    </td>
                                                    <td class="task-name-cell">
                                                        <span class="font-weight-bold text-white"><?php echo $taskDisplayName; ?></span>
                                                    </td>
                                                    <td class="task-desc-cell">
                                                        <div><?php echo htmlspecialchars($row['description']); ?></div>
                                                        <?php if (!empty($row['specifics'])): ?>
                                                            <small class="text-info d-block mt-1"><i class="bi bi-info-circle me-1"></i><?php echo htmlspecialchars($row['specifics']); ?></small>
                                                        <?php endif; ?>
                                                        <?php if ($canManageTasks && !empty($assignedText)): ?>
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
                                            <tr id="noFilteredTasksRow" style="display:none;">
                                                <td colspan="8" class="text-center py-4" style="background: rgba(16, 23, 38, 0.4);">
                                                    <i class="bi bi-funnel text-warning" style="font-size: 1.8rem; display:block; margin-bottom:8px;"></i>
                                                    <div class="text-white font-weight-bold mb-1" style="font-size:0.95rem;">No tasks match your selected filter criteria.</div>
                                                    <div id="noResultsFilterSummary" class="text-muted small mb-3"></div>
                                                    <button type="button" class="btn btn-sm btn-outline-warning font-weight-bold" onclick="resetAllFilters()">
                                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters &amp; View All Tasks
                                                    </button>
                                                </td>
                                            </tr>
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
                <form method="POST" id="addTaskForm">
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
                                    $rawFullName = trim($u['firstName'] . ' ' . $u['lastName']);
                                ?>
                                    <label class="member-check-item d-flex align-items-center p-2 mb-1 rounded" style="cursor:pointer;">
                                        <input type="checkbox" name="specific_member_ids[]" value="<?php echo $u['id']; ?>" class="form-check-input member-circle-check me-2" data-card="<?php echo htmlspecialchars($uCard); ?>" data-category="<?php echo htmlspecialchars($uCat); ?>" data-name="<?php echo htmlspecialchars($rawFullName); ?>" onchange="updateSelectedCount('add')">
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
                <form method="POST" id="editTaskForm">
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
                                    $rawFullName = trim($u['firstName'] . ' ' . $u['lastName']);
                                ?>
                                    <label class="member-check-item d-flex align-items-center p-2 mb-1 rounded" style="cursor:pointer;">
                                        <input type="checkbox" name="specific_member_ids[]" value="<?php echo $u['id']; ?>" class="form-check-input member-circle-check me-2" data-card="<?php echo htmlspecialchars($uCard); ?>" data-category="<?php echo htmlspecialchars($uCat); ?>" data-name="<?php echo htmlspecialchars($rawFullName); ?>" onchange="updateSelectedCount('edit')">
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

    <!-- ── CARD / CATEGORY MISMATCH WARNING MODAL ── -->
    <div class="modal fade" id="cardCatMismatchWarningModal" tabindex="-1" aria-labelledby="cardCatMismatchWarningModalLabel" aria-hidden="true" style="z-index: 1065;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background:#192436; border:1px solid #f59e0b; color:#fff; box-shadow: 0 10px 30px rgba(0,0,0,0.6);">
                <div class="modal-header" style="border-bottom:1px solid #293647; background:rgba(245, 158, 11, 0.12);">
                    <h5 class="modal-title text-warning" id="cardCatMismatchWarningModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Different Card / Category Detected
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-white mb-3" style="font-size:0.95rem;">
                        The selected members belong to <strong class="text-warning">different Cards or Categories</strong>:
                    </p>

                    <div class="mb-3 p-3 rounded" style="background:#101726; border:1px solid #293647;">
                        <label class="small text-muted text-uppercase font-weight-bold d-block mb-2">Selected Members Breakdown:</label>
                        <div id="warningGroupsList" class="d-flex flex-column gap-2"></div>
                    </div>

                    <div class="p-2 px-3 rounded mb-1" style="background:rgba(13, 110, 253, 0.12); border:1px solid rgba(13, 110, 253, 0.3);">
                        <small class="text-info d-block">
                            <i class="bi bi-info-circle me-1"></i> If you click <strong>Proceed Anyway</strong>, multiple task IDs (<span id="warningPreviewIdsText" class="fw-bold text-white"></span>) will be generated and assigned so each member's card & category is covered.
                        </small>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #293647; justify-content:space-between;">
                    <button type="button" class="btn btn-outline-light" onclick="backToSetMembers()">
                        <i class="bi bi-arrow-left me-1"></i>Set & Select Members
                    </button>
                    <button type="button" class="btn btn-warning text-dark font-weight-bold" onclick="proceedAnywayWithMismatch()">
                        <i class="bi bi-check2-circle me-1"></i>Proceed Anyway
                    </button>
                </div>
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
    var pendingMismatchForm = null;
    var warningModalInstance = null;

    function getSelectedMemberGroupMap(prefix) {
        var checkedBoxes = document.querySelectorAll('#' + prefix + '_member_list input[type="checkbox"]:checked');
        var groupMap = {};

        checkedBoxes.forEach(function(cb) {
            var c = cb.getAttribute('data-card') || 'Diamond';
            var cat = cb.getAttribute('data-category') || 'B';
            var name = cb.getAttribute('data-name') || cb.value;
            var key = c.trim() + '|' + cat.trim();

            if (!groupMap[key]) {
                groupMap[key] = {
                    card: c.trim(),
                    category: cat.trim(),
                    members: []
                };
            }
            groupMap[key].members.push(name);
        });

        return groupMap;
    }

    function showDifferentCardCatModal(form, prefix, groupMap) {
        pendingMismatchForm = form;
        var groupsList = document.getElementById('warningGroupsList');
        var previewIdsText = document.getElementById('warningPreviewIdsText');

        if (groupsList) {
            groupsList.innerHTML = '';
            var previewCodes = [];

            Object.keys(groupMap).forEach(function(key) {
                var g = groupMap[key];
                var p = (g.card.charAt(0) + g.category.charAt(0)).toUpperCase();
                previewCodes.push(p);

                var itemDiv = document.createElement('div');
                itemDiv.className = 'p-2 rounded';
                itemDiv.style.background = '#151f30';
                itemDiv.style.border = '1px solid #293647';
                itemDiv.innerHTML = `
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div>
                            <span class="badge bg-primary me-1">${escapeHtml(g.card)}</span>
                            <span class="badge bg-info text-dark">${escapeHtml(g.category)}</span>
                        </div>
                        <span class="badge bg-secondary" style="font-size:0.75rem;">${g.members.length} member${g.members.length > 1 ? 's' : ''}</span>
                    </div>
                    <div class="small text-white" style="font-size:0.83rem;">
                        ${g.members.map(m => escapeHtml(m)).join(', ')}
                    </div>
                `;
                groupsList.appendChild(itemDiv);
            });

            if (previewIdsText) {
                previewIdsText.textContent = previewCodes.join(', ');
            }
        }

        var modalEl = document.getElementById('cardCatMismatchWarningModal');
        if (modalEl) {
            warningModalInstance = bootstrap.Modal.getInstance(modalEl);
            if (!warningModalInstance) {
                warningModalInstance = new bootstrap.Modal(modalEl);
            }
            warningModalInstance.show();
        }
    }

    function proceedAnywayWithMismatch() {
        if (warningModalInstance) {
            warningModalInstance.hide();
        }
        if (pendingMismatchForm) {
            pendingMismatchForm.dataset.bypassMismatch = "true";
            pendingMismatchForm.submit();
        }
    }

    function backToSetMembers() {
        if (warningModalInstance) {
            warningModalInstance.hide();
        }
    }

    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, function(m) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[m];
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var warningModalEl = document.getElementById('cardCatMismatchWarningModal');
        if (warningModalEl) {
            warningModalEl.addEventListener('hidden.bs.modal', function () {
                var addM = document.getElementById('addTaskModal');
                var editM = document.getElementById('editTaskModal');
                if ((addM && addM.classList.contains('show')) || (editM && editM.classList.contains('show'))) {
                    document.body.classList.add('modal-open');
                }
            });
        }

        var addForm = document.getElementById('addTaskForm');
        if (addForm) {
            addForm.addEventListener('submit', function (e) {
                if (addForm.dataset.bypassMismatch === "true") {
                    addForm.dataset.bypassMismatch = "";
                    return true;
                }
                var isMember = document.getElementById('add_assign_member') && document.getElementById('add_assign_member').checked;
                if (isMember) {
                    var groupMap = getSelectedMemberGroupMap('add');
                    var keys = Object.keys(groupMap);
                    if (keys.length > 1) {
                        e.preventDefault();
                        showDifferentCardCatModal(addForm, 'add', groupMap);
                        return false;
                    }
                }
            });
        }

        var editForm = document.getElementById('editTaskForm');
        if (editForm) {
            editForm.addEventListener('submit', function (e) {
                if (editForm.dataset.bypassMismatch === "true") {
                    editForm.dataset.bypassMismatch = "";
                    return true;
                }
                var isMember = document.getElementById('edit_assign_member') && document.getElementById('edit_assign_member').checked;
                if (isMember) {
                    var groupMap = getSelectedMemberGroupMap('edit');
                    var keys = Object.keys(groupMap);
                    if (keys.length > 1) {
                        e.preventDefault();
                        showDifferentCardCatModal(editForm, 'edit', groupMap);
                        return false;
                    }
                }
            });
        }
    });

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

    // Global reset function accessible from buttons, chips, and modals
    window.resetAllFilters = function() {
        var searchInput = document.getElementById('searchInput');
        var timeFilter = document.getElementById('timeFilter');
        var cardFilter = document.getElementById('cardFilter');
        var categoryFilter = document.getElementById('categoryFilter');

        if (searchInput) searchInput.value = '';
        if (timeFilter) timeFilter.value = '';
        if (cardFilter) cardFilter.value = '';
        if (categoryFilter) categoryFilter.value = '';

        if (window.applyTaskFilters) {
            window.applyTaskFilters();
        }
    };

    // Client-side search and filtering
    document.addEventListener('DOMContentLoaded', function () {
        var searchInput = document.getElementById('searchInput');
        var timeFilter = document.getElementById('timeFilter');
        var cardFilter = document.getElementById('cardFilter');
        var categoryFilter = document.getElementById('categoryFilter');
        var tableRows = document.querySelectorAll('#tasksTable tbody tr.task-row');
        var noFilteredRow = document.getElementById('noFilteredTasksRow');

        function isDateInPeriod(dateStr, period) {
            if (!dateStr || !period) return false;
            var parts = dateStr.split('-');
            if (parts.length < 3) return false;
            
            var taskY = parseInt(parts[0], 10);
            var taskM = parseInt(parts[1], 10) - 1; // 0-indexed month
            var taskD = parseInt(parts[2], 10);
            
            var now = new Date();
            var nowY = now.getFullYear();
            var nowM = now.getMonth();
            var nowD = now.getDate();

            // Exact calendar midnight comparisons
            var todayMidnight = new Date(nowY, nowM, nowD).getTime();
            var taskMidnight = new Date(taskY, taskM, taskD).getTime();
            var diffDays = Math.round((todayMidnight - taskMidnight) / 86400000); // 1000 * 60 * 60 * 24

            if (period === 'daily') {
                return diffDays === 0;
            } else if (period === 'yesterday') {
                return diffDays === 1;
            } else if (period === 'last_3_days') {
                return diffDays >= 0 && diffDays <= 2;
            } else if (period === 'weekly') {
                var dayOfWeek = now.getDay();
                var diffToMon = (dayOfWeek === 0 ? -6 : 1 - dayOfWeek);
                var startOfWeek = new Date(nowY, nowM, nowD + diffToMon).getTime();
                var endOfWeek = new Date(nowY, nowM, nowD + diffToMon + 6, 23, 59, 59, 999).getTime();
                return taskMidnight >= startOfWeek && taskMidnight <= endOfWeek;
            } else if (period === 'previous_week') {
                var dayOfWeek = now.getDay();
                var diffToMon = (dayOfWeek === 0 ? -6 : 1 - dayOfWeek);
                var startOfPrevWeek = new Date(nowY, nowM, nowD + diffToMon - 7).getTime();
                var endOfPrevWeek = new Date(nowY, nowM, nowD + diffToMon - 1, 23, 59, 59, 999).getTime();
                return taskMidnight >= startOfPrevWeek && taskMidnight <= endOfPrevWeek;
            } else if (period === 'monthly') {
                return (taskY === nowY && taskM === nowM);
            } else if (period === 'previous_month') {
                var prevMonthY = (nowM === 0) ? nowY - 1 : nowY;
                var prevMonthM = (nowM === 0) ? 11 : nowM - 1;
                return (taskY === prevMonthY && taskM === prevMonthM);
            } else if (period === 'yearly') {
                return (taskY === nowY);
            }
            return true;
        }

        function updateActiveFilterChips(searchVal, timeVal, cardVal, catVal) {
            var strip = document.getElementById('activeFiltersStrip');
            var container = document.getElementById('activeFilterBadges');
            if (!strip || !container) return;

            var chips = [];
            if (searchVal) {
                chips.push({ label: 'Search: "' + searchVal + '"', clear: function() { if (searchInput) { searchInput.value = ''; filterTable(); } } });
            }
            if (timeVal) {
                var timeText = timeFilter && timeFilter.options[timeFilter.selectedIndex] ? timeFilter.options[timeFilter.selectedIndex].text : timeVal;
                chips.push({ label: 'Time: ' + timeText, clear: function() { if (timeFilter) { timeFilter.value = ''; filterTable(); } } });
            }
            if (cardVal) {
                chips.push({ label: 'Card: ' + (cardFilter ? cardFilter.value : cardVal), clear: function() { if (cardFilter) { cardFilter.value = ''; filterTable(); } } });
            }
            if (catVal) {
                chips.push({ label: 'Category: ' + (categoryFilter ? categoryFilter.value : catVal), clear: function() { if (categoryFilter) { categoryFilter.value = ''; filterTable(); } } });
            }

            if (chips.length > 0) {
                strip.style.setProperty('display', 'flex', 'important');
                container.innerHTML = '';
                chips.forEach(function(chip) {
                    var badge = document.createElement('span');
                    badge.className = 'badge bg-secondary d-inline-flex align-items-center gap-1';
                    badge.style.cssText = 'background:#1e293b !important; border:1px solid #334155; font-size:0.78rem; padding:4px 8px; color:#cbd5e1;';
                    badge.innerHTML = chip.label + ' <a href="javascript:void(0)" class="text-danger ms-1" style="text-decoration:none; font-weight:bold;">&times;</a>';
                    badge.querySelector('a').addEventListener('click', function(e) {
                        e.preventDefault();
                        chip.clear();
                    });
                    container.appendChild(badge);
                });
            } else {
                strip.style.setProperty('display', 'none', 'important');
                container.innerHTML = '';
            }
        }

        function filterTable() {
            var searchVal = searchInput ? searchInput.value.toLowerCase().trim() : '';
            var timeVal = timeFilter ? timeFilter.value.toLowerCase().trim() : '';
            var cardVal = cardFilter ? cardFilter.value.toLowerCase().trim() : '';
            var catVal = categoryFilter ? categoryFilter.value.toLowerCase().trim() : '';

            var visibleCount = 0;

            tableRows.forEach(function (row) {
                var name = row.querySelector('.task-name-cell') ? row.querySelector('.task-name-cell').textContent.toLowerCase() : '';
                var desc = row.querySelector('.task-desc-cell') ? row.querySelector('.task-desc-cell').textContent.toLowerCase() : '';
                
                // Collect row cards from data-card attribute AND badge-card texts
                var dataCard = (row.getAttribute('data-card') || '').toLowerCase();
                var cardBadgeTexts = Array.from(row.querySelectorAll('.badge-card')).map(function(el) { return el.textContent.toLowerCase().trim(); });
                var allRowCards = dataCard.split(',').map(function(s) { return s.trim(); }).concat(cardBadgeTexts).filter(Boolean);

                // Collect row categories from data-category attribute AND badge-category texts
                var dataCat = (row.getAttribute('data-category') || '').toLowerCase();
                var catBadgeTexts = Array.from(row.querySelectorAll('.badge-category')).map(function(el) { return el.textContent.toLowerCase().trim(); });
                var allRowCats = dataCat.split(',').map(function(s) { return s.trim(); }).concat(catBadgeTexts).filter(Boolean);

                var expDate = row.getAttribute('data-expiry-date') || '';
                var crtDate = row.getAttribute('data-created-date') || '';

                var matchesSearch = !searchVal || desc.includes(searchVal) || name.includes(searchVal);
                
                // Card filter match (handles single or multi-card tasks like Silver, Diamond)
                var matchesCard = !cardVal || allRowCards.some(function(c) {
                    return c === cardVal || c.includes(cardVal) || cardVal.includes(c);
                });

                // Category filter match (handles single or multi-category tasks)
                var matchesCat = !catVal || allRowCats.some(function(c) {
                    return c === catVal;
                });

                var matchesTime = !timeVal || isDateInPeriod(expDate, timeVal) || isDateInPeriod(crtDate, timeVal);

                if (matchesSearch && matchesCard && matchesCat && matchesTime) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Update active filter chips
            updateActiveFilterChips(searchVal, timeVal, cardVal, catVal);

            // Handle empty state
            if (noFilteredRow) {
                if (visibleCount === 0 && tableRows.length > 0) {
                    noFilteredRow.style.display = '';
                    var summaryEl = document.getElementById('noResultsFilterSummary');
                    if (summaryEl) {
                        var activeList = [];
                        if (timeVal) activeList.push('Time: <strong>' + (timeFilter.options[timeFilter.selectedIndex]?.text || timeVal) + '</strong>');
                        if (cardVal) activeList.push('Card: <strong>' + (cardFilter ? cardFilter.value : cardVal) + '</strong>');
                        if (catVal) activeList.push('Category: <strong>' + (categoryFilter ? categoryFilter.value : catVal) + '</strong>');
                        if (searchVal) activeList.push('Search: <strong>"' + searchVal + '"</strong>');
                        
                        var msg = activeList.length > 0 ? ('Active filters: ' + activeList.join(' + ')) : '';
                        if (timeVal === 'yesterday') {
                            msg += '<br><span class="text-warning small"><i class="bi bi-info-circle me-1"></i>Note: No tasks in the system were created or scheduled to expire yesterday.</span>';
                        }
                        summaryEl.innerHTML = msg;
                    }
                } else {
                    noFilteredRow.style.display = 'none';
                }
            }
        }

        window.applyTaskFilters = filterTable;

        // Auto-compatibility on card change: if selected category has 0 tasks under this card, reset category
        if (cardFilter) {
            cardFilter.addEventListener('change', function() {
                var cVal = cardFilter.value.toLowerCase().trim();
                var catVal = categoryFilter ? categoryFilter.value.toLowerCase().trim() : '';
                if (cVal && catVal) {
                    var hasCompat = false;
                    tableRows.forEach(function(row) {
                        var dCard = (row.getAttribute('data-card') || '').toLowerCase();
                        var dCat = (row.getAttribute('data-category') || '').toLowerCase();
                        if (dCard.includes(cVal) && dCat.includes(catVal)) {
                            hasCompat = true;
                        }
                    });
                    if (!hasCompat && categoryFilter) {
                        categoryFilter.value = ''; // auto-reset category to show this card's tasks!
                    }
                }
                filterTable();
            });
        }

        // Auto-compatibility on category change: if selected card has 0 tasks under this category, reset card
        if (categoryFilter) {
            categoryFilter.addEventListener('change', function() {
                var catVal = categoryFilter.value.toLowerCase().trim();
                var cVal = cardFilter ? cardFilter.value.toLowerCase().trim() : '';
                if (catVal && cVal) {
                    var hasCompat = false;
                    tableRows.forEach(function(row) {
                        var dCard = (row.getAttribute('data-card') || '').toLowerCase();
                        var dCat = (row.getAttribute('data-category') || '').toLowerCase();
                        if (dCard.includes(cVal) && dCat.includes(catVal)) {
                            hasCompat = true;
                        }
                    });
                    if (!hasCompat && cardFilter) {
                        cardFilter.value = ''; // auto-reset card to show this category's tasks!
                    }
                }
                filterTable();
            });
        }

        if (searchInput) searchInput.addEventListener('keyup', filterTable);
        if (timeFilter) timeFilter.addEventListener('change', filterTable);
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
