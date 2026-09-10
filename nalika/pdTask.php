<?php
include "connection.php";
include "auth.php";
// $loggedUserId, $loggedRole, $loggedArea set by auth.php

require_once "permissions.php";
if (!hasPermission("pdTask") && !hasPermission("pdMyTasks")) {
    header("Location: dashboard.php");
    exit();
}

// Auto-create member_tasks table if it doesn't exist
$tableQuery = "CREATE TABLE IF NOT EXISTS `member_tasks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `task_code` VARCHAR(50) NULL,
  `task_name` VARCHAR(255) NOT NULL,
  `task_type` VARCHAR(50) DEFAULT 'Weekly',
  `status` ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
  `start_time` DATETIME DEFAULT NULL,
  `end_time` DATETIME DEFAULT NULL,
  `duration_minutes` INT DEFAULT 0,
  `submitted_at` DATETIME DEFAULT NULL,
  `document_path` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $tableQuery);

@mysqli_query($conn, "ALTER TABLE `member_tasks` ADD COLUMN `task_code` VARCHAR(50) NULL AFTER `user_id`");

// Backfill empty task_code rows in member_tasks
$unassignedMember = mysqli_query($conn, "SELECT id FROM member_tasks WHERE user_id = '$loggedUserId' AND (task_code IS NULL OR task_code = '') ORDER BY id ASC");
if ($unassignedMember && mysqli_num_rows($unassignedMember) > 0) {
    $uInfo = mysqli_query($conn, "SELECT card, category FROM users WHERE id = '$loggedUserId'");
    $uCard = 'Diamond';
    $uCat = 'B';
    if ($uInfo && $uRow = mysqli_fetch_assoc($uInfo)) {
        if (!empty($uRow['card'])) $uCard = $uRow['card'];
        if (!empty($uRow['category'])) $uCat = $uRow['category'];
    }
    $prefix = strtoupper(substr(trim($uCard), 0, 1)) . strtoupper(substr(trim($uCat), 0, 1));

    while ($mRow = mysqli_fetch_assoc($unassignedMember)) {
        $mId = $mRow['id'];
        $cntRes = mysqli_query($conn, "SELECT task_code FROM member_tasks WHERE user_id = '$loggedUserId' AND task_code LIKE '{$prefix}%' AND id != '$mId'");
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
        mysqli_query($conn, "UPDATE member_tasks SET task_code = '$newCode' WHERE id = '$mId'");
    }
}

// Insert a default task if user has no tasks yet
$checkTasks = mysqli_query($conn, "SELECT id FROM member_tasks WHERE user_id = '$loggedUserId'");
if (mysqli_num_rows($checkTasks) == 0) {
    $uInfo = mysqli_query($conn, "SELECT card, category FROM users WHERE id = '$loggedUserId'");
    $uCard = 'Diamond';
    $uCat = 'B';
    if ($uInfo && $uRow = mysqli_fetch_assoc($uInfo)) {
        if (!empty($uRow['card'])) $uCard = $uRow['card'];
        if (!empty($uRow['category'])) $uCat = $uRow['category'];
    }
    $prefix = strtoupper(substr(trim($uCard), 0, 1)) . strtoupper(substr(trim($uCat), 0, 1));
    $defaultCode = $prefix . '1';
    $initQuery = "INSERT INTO member_tasks (user_id, task_code, task_name, task_type, status) VALUES ('$loggedUserId', '$defaultCode', 'quran', 'Weekly', 'Pending')";
    mysqli_query($conn, $initQuery);
}

// Handle Form Submissions
$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'start_task') {
        $taskId = intval($_POST['task_id']);
        $now = date('Y-m-d H:i:s');
        $startSql = "UPDATE member_tasks SET status = 'In Progress', start_time = '$now' WHERE id = '$taskId' AND user_id = '$loggedUserId'";
        if (mysqli_query($conn, $startSql)) {
            $message = "Task started successfully!";
            $messageType = "success";
        } else {
            $message = "Error starting task: " . mysqli_error($conn);
            $messageType = "danger";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'submit_task') {
        $taskId = intval($_POST['task_id']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
        $now = date('Y-m-d H:i:s');
        
        // Handle File Upload if provided
        $docPath = NULL;
        if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/documents/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time() . '_' . basename($_FILES['document']['name']);
            $targetFilePath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['document']['tmp_name'], $targetFilePath)) {
                $docPath = $targetFilePath;
            }
        }

        // Calculate Duration
        $getStart = mysqli_query($conn, "SELECT start_time FROM member_tasks WHERE id = '$taskId' AND user_id = '$loggedUserId'");
        $startRow = mysqli_fetch_assoc($getStart);
        $duration = 0;
        if ($startRow && !empty($startRow['start_time'])) {
            $startTime = strtotime($startRow['start_time']);
            $endTime = strtotime($now);
            $duration = round(($endTime - $startTime) / 60); // minutes
        }

        $docSql = $docPath ? ", document_path = '" . mysqli_real_escape_string($conn, $docPath) . "'" : "";
        $submitSql = "UPDATE member_tasks SET status = 'Completed', end_time = '$now', duration_minutes = '$duration', submitted_at = '$now', notes = '$notes' $docSql WHERE id = '$taskId' AND user_id = '$loggedUserId'";
        
        if (mysqli_query($conn, $submitSql)) {
            // Create next pending task automatically for workflow
            $currentTask = mysqli_fetch_assoc(mysqli_query($conn, "SELECT task_name, task_type FROM member_tasks WHERE id = '$taskId'"));
            if ($currentTask) {
                $taskName = mysqli_real_escape_string($conn, $currentTask['task_name']);
                $taskType = mysqli_real_escape_string($conn, $currentTask['task_type']);
                
                $uInfo = mysqli_query($conn, "SELECT card, category FROM users WHERE id = '$loggedUserId'");
                $uCard = 'Diamond';
                $uCat = 'B';
                if ($uInfo && $uRow = mysqli_fetch_assoc($uInfo)) {
                    if (!empty($uRow['card'])) $uCard = $uRow['card'];
                    if (!empty($uRow['category'])) $uCat = $uRow['category'];
                }
                $prefix = strtoupper(substr(trim($uCard), 0, 1)) . strtoupper(substr(trim($uCat), 0, 1));
                $cntRes = mysqli_query($conn, "SELECT task_code FROM member_tasks WHERE user_id = '$loggedUserId' AND task_code LIKE '{$prefix}%'");
                $maxNum = 0;
                if ($cntRes) {
                    while ($cRow = mysqli_fetch_assoc($cntRes)) {
                        if (!empty($cRow['task_code'])) {
                            $numPart = intval(substr($cRow['task_code'], strlen($prefix)));
                            if ($numPart > $maxNum) $maxNum = $numPart;
                        }
                    }
                }
                $nextCode = $prefix . ($maxNum + 1);

                mysqli_query($conn, "INSERT INTO member_tasks (user_id, task_code, task_name, task_type, status) VALUES ('$loggedUserId', '$nextCode', '$taskName', '$taskType', 'Pending')");
            }
            $message = "Task submitted successfully!";
            $messageType = "success";
        } else {
            $message = "Error submitting task: " . mysqli_error($conn);
            $messageType = "danger";
        }
    }
}

// Fetch Current Active / Today's Task
$activeTaskQuery = mysqli_query($conn, "SELECT * FROM member_tasks WHERE user_id = '$loggedUserId' AND status IN ('Pending', 'In Progress') ORDER BY id DESC LIMIT 1");
$todayTask = mysqli_fetch_assoc($activeTaskQuery);

// Fetch Statistics
$completedTotalRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM member_tasks WHERE user_id = '$loggedUserId' AND status = 'Completed'");
$totalCompleted = mysqli_fetch_assoc($completedTotalRes)['cnt'] ?? 0;

$firstDayMonth = date('Y-m-01 00:00:00');
$completedMonthRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM member_tasks WHERE user_id = '$loggedUserId' AND status = 'Completed' AND submitted_at >= '$firstDayMonth'");
$thisMonthCompleted = mysqli_fetch_assoc($completedMonthRes)['cnt'] ?? 0;

$avgTimeRes = mysqli_query($conn, "SELECT AVG(duration_minutes) as avg_dur FROM member_tasks WHERE user_id = '$loggedUserId' AND status = 'Completed' AND duration_minutes > 0");
$avgDuration = mysqli_fetch_assoc($avgTimeRes)['avg_dur'];
$avgTimeFormatted = ($avgDuration !== null && $avgDuration > 0) ? round($avgDuration) . " m" : "-";

// Fetch Task History
$historyQuery = mysqli_query($conn, "SELECT * FROM member_tasks WHERE user_id = '$loggedUserId' ORDER BY id DESC");
?>
<!doctype html>
<html lang="en">
<?php include "header.php"; ?>

<style>
/* ── Today's Task Hero Banner ── */
.today-task-card {
    background: linear-gradient(135deg, #15803d 0%, #166534 100%);
    border-radius: 16px;
    padding: 28px 32px;
    color: #ffffff;
    margin-bottom: 24px;
    box-shadow: 0 10px 30px rgba(21, 128, 61, 0.25);
    position: relative;
    overflow: hidden;
}

.today-task-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    pointer-events: none;
}

.today-task-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 12px;
}

.today-task-name {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 16px;
    text-transform: lowercase;
    opacity: 0.95;
}

.today-task-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 20px;
}

.today-task-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}

.btn-start-task {
    background: #ffffff;
    color: #15803d;
    font-weight: 700;
    font-size: 0.9rem;
    padding: 10px 24px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-start-task:hover {
    background: #f8fafc;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    color: #166534;
}

.btn-start-task:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-submit-task {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    font-weight: 600;
    font-size: 0.9rem;
    padding: 10px 24px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.btn-submit-task:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.5);
    color: #ffffff;
}

/* ── Stats Row ── */
.task-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 28px;
}

@media (max-width: 768px) {
    .task-stats-grid {
        grid-template-columns: 1fr;
    }
}

.task-stat-card {
    background: #192436;
    border: 1px solid #293647;
    border-radius: 12px;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.task-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

.task-stat-icon.blue {
    background: rgba(13, 110, 253, 0.15);
    color: #38bdf8;
}

.task-stat-icon.purple {
    background: rgba(139, 92, 246, 0.15);
    color: #a78bfa;
}

.task-stat-icon.amber {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
}

.task-stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
}

.task-stat-label {
    font-size: 0.8rem;
    color: #94a3b8;
    font-weight: 500;
}

/* ── History Table Card ── */
.history-card {
    background: #192436;
    border: 1px solid #293647;
    border-radius: 14px;
    padding: 24px;
}

.history-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.05rem;
    font-weight: 700;
    color: #f8fafc;
    margin-bottom: 20px;
}

.task-history-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.task-history-table th {
    background: #101726;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    padding: 14px 16px;
    border-bottom: 1px solid #293647;
}

.task-history-table th:first-child {
    border-top-left-radius: 8px;
}

.task-history-table th:last-child {
    border-top-right-radius: 8px;
}

.task-history-table td {
    padding: 14px 16px;
    font-size: 0.85rem;
    color: #cbd5e1;
    border-bottom: 1px solid #233042;
    vertical-align: middle;
}

.task-history-table tr:last-child td {
    border-bottom: none;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.completed {
    background: rgba(34, 197, 94, 0.15);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-badge.in-progress {
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.status-badge.pending {
    background: rgba(148, 163, 184, 0.15);
    color: #cbd5e1;
    border: 1px solid rgba(148, 163, 184, 0.3);
}
</style>

<body>
    <?php include "auth.php"; ?>
    <?php include "sidebar.php"; ?>
    <?php include "mainTopBar.php"; ?>

    <div class="breadcome-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcome-list single-page-breadcome">
                        <div class="row">
                            <div class="col-6">
                                <h6 class="mb-0" style="font-size:.9rem;color:#6c757d;">My Tasks</h6>
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

    <!-- Main Content Body -->
    <div class="product-status mg-b-30">
        <div class="container-fluid">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Page Title -->
            <div class="d-flex align-items-center gap-2 mb-4">
                <div class="p-2 rounded bg-success bg-opacity-25 text-success">
                    <i class="bi bi-clipboard-check fs-4"></i>
                </div>
                <h4 class="mb-0 text-white font-weight-bold">My Tasks</h4>
            </div>

            <!-- Today's Task Card -->
            <div class="today-task-card">
                <div class="today-task-header">
                    <i class="bi bi-star-fill text-warning"></i>
                    <span>Today's Task</span>
                </div>
                <?php if ($todayTask): ?>
                    <div class="today-task-name">
                        <?php echo htmlspecialchars($todayTask['task_name']); ?>
                    </div>
                    <div class="today-task-badge">
                        <i class="bi bi-calendar3"></i>
                        <span><?php echo htmlspecialchars($todayTask['task_type']); ?></span>
                    </div>

                    <div class="today-task-actions">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="start_task">
                            <input type="hidden" name="task_id" value="<?php echo $todayTask['id']; ?>">
                            <button type="submit" class="btn-start-task" <?php echo ($todayTask['status'] === 'In Progress') ? 'disabled' : ''; ?>>
                                <i class="bi bi-play-fill"></i>
                                <?php echo ($todayTask['status'] === 'In Progress') ? 'Task In Progress' : 'Start Task'; ?>
                            </button>
                        </form>

                        <button type="button" class="btn-submit-task" data-bs-toggle="modal" data-bs-target="#submitTaskModal">
                            <i class="bi bi-upload"></i>
                            <span>Submit Task</span>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="today-task-name">All tasks completed for today!</div>
                    <div class="today-task-badge"><i class="bi bi-check-all"></i><span>Done</span></div>
                <?php endif; ?>
            </div>

            <!-- Task Summary Stats -->
            <div class="task-stats-grid">
                <div class="task-stat-card">
                    <div class="task-stat-icon blue">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <div>
                        <div class="task-stat-value"><?php echo $totalCompleted; ?></div>
                        <div class="task-stat-label">Total Completed</div>
                    </div>
                </div>

                <div class="task-stat-card">
                    <div class="task-stat-icon purple">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div>
                        <div class="task-stat-value"><?php echo $thisMonthCompleted; ?></div>
                        <div class="task-stat-label">This Month</div>
                    </div>
                </div>

                <div class="task-stat-card">
                    <div class="task-stat-icon amber">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <div class="task-stat-value"><?php echo $avgTimeFormatted; ?></div>
                        <div class="task-stat-label">Avg Time</div>
                    </div>
                </div>
            </div>

            <!-- Task History Table -->
            <div class="history-card">
                <div class="history-card-header">
                    <i class="bi bi-clock-history text-info"></i>
                    <span>Task History</span>
                </div>

                <div class="table-responsive">
                    <table class="task-history-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>TASK</th>
                                <th>START TIME</th>
                                <th>END TIME</th>
                                <th>DURATION</th>
                                <th>SUBMITTED</th>
                                <th>DOCUMENT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($historyQuery) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($historyQuery)): ?>
                                    <tr>
                                        <td><span class="badge bg-primary font-weight-bold" style="font-size:0.8rem;"><?php echo htmlspecialchars($row['task_code'] ?? ('T' . $row['id'])); ?></span></td>
                                        <td class="font-weight-bold text-white"><?php echo htmlspecialchars($row['task_name']); ?></td>
                                        <td>
                                            <?php echo !empty($row['start_time']) ? date('d M Y, h:i A', strtotime($row['start_time'])) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php echo !empty($row['end_time']) ? date('d M Y, h:i A', strtotime($row['end_time'])) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php echo ($row['duration_minutes'] > 0) ? $row['duration_minutes'] . ' mins' : '-'; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] === 'Completed'): ?>
                                                <span class="status-badge completed">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                    <?php echo !empty($row['submitted_at']) ? date('d M Y', strtotime($row['submitted_at'])) : 'Completed'; ?>
                                                </span>
                                            <?php elseif ($row['status'] === 'In Progress'): ?>
                                                <span class="status-badge in-progress">
                                                    <i class="bi bi-play-circle-fill"></i> In Progress
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge pending">
                                                    <i class="bi bi-hourglass-split"></i> Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['document_path']) && file_exists($row['document_path'])): ?>
                                                <a href="viewDocument.php?file=<?php echo urlencode($row['document_path']); ?>" target="_blank" class="btn btn-sm btn-outline-info text-decoration-none">
                                                    <i class="bi bi-eye me-1"></i> View Document
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No task history found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Submit Task Modal -->
    <?php if ($todayTask): ?>
    <div class="modal fade" id="submitTaskModal" tabindex="-1" aria-labelledby="submitTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background:#192436; border:1px solid #293647; color:#fff;">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header" style="border-bottom:1px solid #293647;">
                        <h5 class="modal-title text-white" id="submitTaskModalLabel">
                            <i class="bi bi-upload text-success me-2"></i>Submit Task
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="submit_task">
                        <input type="hidden" name="task_id" value="<?php echo $todayTask['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small font-weight-bold">Task</label>
                            <input type="text" class="form-control" style="background:#101726; border-color:#293647; color:#fff;" value="<?php echo htmlspecialchars($todayTask['task_name']); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label text-muted small font-weight-bold">Remarks / Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" style="background:#101726; border-color:#293647; color:#fff;" placeholder="Enter details about your task submission..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="document" class="form-label text-muted small font-weight-bold">Attach Document (Optional)</label>
                            <input type="file" class="form-control" id="document" name="document" style="background:#101726; border-color:#293647; color:#fff;">
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #293647;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success font-weight-bold">
                            <i class="bi bi-check2-circle me-1"></i> Submit Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php include "footer.php"; ?>
    <!-- Script dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
