<?php
// fetchUserPerformance.php
// Returns comprehensive performance analytics for a specific user and time period
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Karachi');
} else {
    @date_default_timezone_set('Asia/Karachi');
}

include "connection.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$sessionUser = $_SESSION['user'] ?? [];
$sessionUserId = intval($sessionUser['id'] ?? 0);
$sessionRole = strtolower(trim($sessionUser['role'] ?? ''));
$sessionUsername = strtolower(trim($sessionUser['username'] ?? ''));

// Privileged roles that can inspect other users' reports (Leadership / Admin / Committee / Representative)
$canInspectOthers = in_array($sessionRole, ['md', 'dg', 'admin', 'administrator', 'adminsir', 'committee', 'representative'])
                    || (stripos($sessionRole, 'admin') !== false)
                    || (stripos($sessionUsername, 'admin') !== false);

$reqUserId = intval($_GET['user_id'] ?? 0);

// If non-privileged or no requested ID provided, restrict strictly to logged in user ID
if (!$canInspectOthers || $reqUserId <= 0) {
    $userId = $sessionUserId;
} else {
    $userId = $reqUserId;
}

$period = strtolower(trim($_GET['period'] ?? 'all'));

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid User ID']);
    exit();
}

// 1. Fetch User Info
$uStmt = $conn->prepare("SELECT id, firstName, lastName, username, email, phone, area, role, card, category, is_active, approval_status, date_of_joining FROM users WHERE id = ?");
$uStmt->bind_param("i", $userId);
$uStmt->execute();
$uRes = $uStmt->get_result();
$user = $uRes->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

// Resolve Area Name
$areaName = $user['area'];
if (is_numeric($user['area'])) {
    $aRes = mysqli_query($conn, "SELECT areaName FROM dars_areas WHERE id = " . intval($user['area']));
    if ($aRes && $aRow = mysqli_fetch_assoc($aRes)) {
        $areaName = $aRow['areaName'];
    }
}
$user['areaName'] = $areaName ?: '—';
$user['fullName'] = trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? ''));
$user['card'] = $user['card'] ?: 'Diamond';
$user['category'] = $user['category'] ?: 'B';

// 2. Date Boundaries
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$threeDaysAgo = date('Y-m-d', strtotime('-2 days'));

// Current Week (Mon-Sun)
$dow = date('N'); // 1 (Mon) - 7 (Sun)
$thisWeekStart = date('Y-m-d', strtotime('-' . ($dow - 1) . ' days'));
$thisWeekEnd = date('Y-m-d', strtotime('+' . (7 - $dow) . ' days'));

// Previous Week (Mon-Sun)
$prevWeekStart = date('Y-m-d', strtotime('-' . ($dow + 6) . ' days'));
$prevWeekEnd = date('Y-m-d', strtotime('-' . $dow . ' days'));

// This Month
$thisMonthStart = date('Y-m-01');
$thisMonthEnd = date('Y-m-t');

// Previous Month
$prevMonthStart = date('Y-m-01', strtotime('first day of last month'));
$prevMonthEnd = date('Y-m-t', strtotime('last day of last month'));

// This Year
$thisYearStart = date('Y-01-01');
$thisYearEnd = date('Y-12-31');

function getDateCondition($col, $period) {
    global $today, $yesterday, $threeDaysAgo, $thisWeekStart, $thisWeekEnd, $prevWeekStart, $prevWeekEnd, $thisMonthStart, $thisMonthEnd, $prevMonthStart, $prevMonthEnd, $thisYearStart, $thisYearEnd;
    
    switch ($period) {
        case 'today':
        case 'daily':
            return "DATE($col) = '$today'";
        case 'yesterday':
            return "DATE($col) = '$yesterday'";
        case 'last_3_days':
            return "DATE($col) BETWEEN '$threeDaysAgo' AND '$today'";
        case 'this_week':
        case 'weekly':
            return "DATE($col) BETWEEN '$thisWeekStart' AND '$thisWeekEnd'";
        case 'last_week':
        case 'previous_week':
            return "DATE($col) BETWEEN '$prevWeekStart' AND '$prevWeekEnd'";
        case 'this_month':
        case 'monthly':
            return "DATE($col) BETWEEN '$thisMonthStart' AND '$thisMonthEnd'";
        case 'last_month':
        case 'previous_month':
            return "DATE($col) BETWEEN '$prevMonthStart' AND '$prevMonthEnd'";
        case 'yearly':
            return "DATE($col) BETWEEN '$thisYearStart' AND '$thisYearEnd'";
        case 'all':
        default:
            return "1=1";
    }
}

// 3. TASKS METRICS
$taskDateCond = getDateCondition('uts.submitted_at', $period);
$taskSubsSql = "SELECT uts.*, t.task_code, t.task_name, t.description, t.expiry_date 
                FROM user_task_submissions uts
                LEFT JOIN admin_tasks t ON t.id = uts.task_id
                WHERE uts.user_id = '$userId' AND $taskDateCond
                ORDER BY uts.submitted_at DESC";
$taskSubsRes = mysqli_query($conn, $taskSubsSql);

$userSubsMap = [];
if ($taskSubsRes) {
    while ($subRow = mysqli_fetch_assoc($taskSubsRes)) {
        $userSubsMap[$subRow['task_id']] = $subRow;
    }
}

// Find assigned tasks for this user
$userCard = mysqli_real_escape_string($conn, $user['card']);
$userCat = mysqli_real_escape_string($conn, $user['category']);

$baseAssignCond = "(FIND_IN_SET('$userId', REPLACE(specific_member_id, ' ', '')) > 0)
                   OR ((specific_member_id IS NULL OR specific_member_id = '' OR specific_member_id = 'NULL')
                       AND (LOWER(TRIM(card)) = LOWER('$userCard') OR FIND_IN_SET(LOWER('$userCard'), REPLACE(LOWER(card), ' ', '')) > 0 OR card LIKE '%$userCard%')
                       AND (LOWER(TRIM(category)) = LOWER('$userCat') OR FIND_IN_SET(LOWER('$userCat'), REPLACE(LOWER(category), ' ', '')) > 0 OR category LIKE '%$userCat%'))";

if ($period !== 'all') {
    $periodCreatedCond = getDateCondition('created_at', $period);
    $periodExpiryCond = getDateCondition('expiry_date', $period);
    $subTaskIds = !empty($userSubsMap) ? implode(',', array_map('intval', array_keys($userSubsMap))) : '0';
    $tasksAssignedSql = "SELECT id, task_code, task_name, description, expiry_date, created_at, card, category, specifics 
                         FROM admin_tasks 
                         WHERE ($baseAssignCond)
                           AND ($periodCreatedCond OR $periodExpiryCond OR id IN ($subTaskIds))
                         ORDER BY id DESC";
} else {
    $tasksAssignedSql = "SELECT id, task_code, task_name, description, expiry_date, created_at, card, category, specifics 
                         FROM admin_tasks 
                         WHERE ($baseAssignCond)
                         ORDER BY id DESC";
}

$tasksAssignedRes = mysqli_query($conn, $tasksAssignedSql);
$assignedTasks = [];
if ($tasksAssignedRes) {
    while ($atRow = mysqli_fetch_assoc($tasksAssignedRes)) {
        $assignedTasks[$atRow['id']] = $atRow;
    }
}

// Build comprehensive tasks list
$comprehensiveTasks = [];
$approvedCount = 0;
$pendingCount = 0;
$lateCount = 0;
$submittedCount = 0;

foreach ($assignedTasks as $tId => $tInfo) {
    $hasSub = isset($userSubsMap[$tId]);
    $sub = $hasSub ? $userSubsMap[$tId] : null;

    $isSubmitted = $hasSub;
    $status = $hasSub ? ($sub['status'] ?? 'Pending') : 'Not Submitted';
    $submittedAt = $hasSub ? $sub['submitted_at'] : null;
    $subNotes = $hasSub ? ($sub['submission_notes'] ?? '') : '';
    
    $isLate = false;
    if ($hasSub && !empty($tInfo['expiry_date']) && !empty($submittedAt)) {
        if (strtotime($submittedAt) > strtotime($tInfo['expiry_date'])) {
            $isLate = true;
            $lateCount++;
        }
    } elseif (!$hasSub && !empty($tInfo['expiry_date']) && strtotime('now') > strtotime($tInfo['expiry_date'])) {
        $isLate = true;
    }

    if ($hasSub) {
        $submittedCount++;
        if ($status === 'Approved') $approvedCount++;
        else $pendingCount++;
    }

    $comprehensiveTasks[] = [
        'task_id' => $tId,
        'task_code' => $tInfo['task_code'] ?: ('T' . $tId),
        'task_name' => $tInfo['task_name'] ?: $tInfo['description'],
        'description' => $tInfo['description'],
        'expiry_date' => $tInfo['expiry_date'],
        'is_submitted' => $isSubmitted,
        'submitted_at' => $submittedAt,
        'status' => $status,
        'is_late' => $isLate,
        'submission_notes' => $subNotes
    ];
}

foreach ($userSubsMap as $tId => $sub) {
    if (!isset($assignedTasks[$tId])) {
        $submittedCount++;
        $status = $sub['status'] ?? 'Pending';
        if ($status === 'Approved') $approvedCount++;
        else $pendingCount++;

        $isLate = false;
        if (!empty($sub['expiry_date']) && !empty($sub['submitted_at'])) {
            if (strtotime($sub['submitted_at']) > strtotime($sub['expiry_date'])) {
                $isLate = true;
                $lateCount++;
            }
        }

        $comprehensiveTasks[] = [
            'task_id' => $tId,
            'task_code' => $sub['task_code'] ?: ('T' . $tId),
            'task_name' => $sub['task_name'] ?: ($sub['description'] ?? 'General Task'),
            'description' => $sub['description'] ?? '',
            'expiry_date' => $sub['expiry_date'] ?? null,
            'is_submitted' => true,
            'submitted_at' => $sub['submitted_at'],
            'status' => $status,
            'is_late' => $isLate,
            'submission_notes' => $sub['submission_notes'] ?? ''
        ];
    }
}

$totalAssigned = max(count($assignedTasks), count($comprehensiveTasks));
$taskCompletionRate = $totalAssigned > 0 ? round(($submittedCount / $totalAssigned) * 100) : ($submittedCount > 0 ? 100 : 0);

// 4. NAMAZ ATTENDANCE METRICS
$namazDateCond = getDateCondition('attendance_date', $period);
$namazSql = "SELECT prayer_name, status, COUNT(*) as cnt 
             FROM namaz_attendance 
             WHERE user_id = '$userId' AND $namazDateCond 
             GROUP BY prayer_name, status";
$namazRes = mysqli_query($conn, $namazSql);

$namazStats = [
    'with_jamaat' => 0,
    'without_jamaat' => 0,
    'missed' => 0,
    'total' => 0,
    'prayers' => [
        'Fajr' => ['with_jamaat' => 0, 'without_jamaat' => 0, 'missed' => 0],
        'Zuhr' => ['with_jamaat' => 0, 'without_jamaat' => 0, 'missed' => 0],
        'Dhuhr' => ['with_jamaat' => 0, 'without_jamaat' => 0, 'missed' => 0],
        'Asr' => ['with_jamaat' => 0, 'without_jamaat' => 0, 'missed' => 0],
        'Maghrib' => ['with_jamaat' => 0, 'without_jamaat' => 0, 'missed' => 0],
        'Isha' => ['with_jamaat' => 0, 'without_jamaat' => 0, 'missed' => 0]
    ]
];

if ($namazRes) {
    while ($nRow = mysqli_fetch_assoc($namazRes)) {
        $rawP = strtolower(trim($nRow['prayer_name']));
        $st = strtolower(trim($nRow['status']));
        $cnt = intval($nRow['cnt']);

        if (isset($namazStats[$st])) {
            $namazStats[$st] += $cnt;
        }
        $namazStats['total'] += $cnt;

        $targetPrayers = ($rawP === 'dhuhr' || $rawP === 'zuhr') ? ['Zuhr', 'Dhuhr'] : [ucfirst($rawP)];

        foreach ($targetPrayers as $pName) {
            if (isset($namazStats['prayers'][$pName]) && isset($namazStats['prayers'][$pName][$st])) {
                $namazStats['prayers'][$pName][$st] += $cnt;
            }
        }
    }
}

$prayersOffered = $namazStats['with_jamaat'] + $namazStats['without_jamaat'];
$namazJamaatRate = $prayersOffered > 0 ? round(($namazStats['with_jamaat'] / $prayersOffered) * 100) : 0;
$namazOfferingRate = $namazStats['total'] > 0 ? round(($prayersOffered / $namazStats['total']) * 100) : 0;

// 5. DARS ATTENDANCE METRICS
$darsDateCond = getDateCondition('dateTime', $period);
$darsSql = "SELECT attendance, timing, COUNT(*) as cnt 
            FROM dars_attendance 
            WHERE user_id = '$userId' AND $darsDateCond 
            GROUP BY attendance, timing";
$darsRes = mysqli_query($conn, $darsSql);

$darsStats = [
    'total' => 0,
    'present' => 0,
    'absent' => 0,
    'on_time' => 0,
    'late' => 0
];

if ($darsRes) {
    while ($dRow = mysqli_fetch_assoc($darsRes)) {
        $att = ucfirst(strtolower($dRow['attendance']));
        $tmg = $dRow['timing'];
        $cnt = intval($dRow['cnt']);

        $darsStats['total'] += $cnt;
        if ($att === 'Present') {
            $darsStats['present'] += $cnt;
            if ($tmg === 'OnTime' || $tmg === 'On Time') $darsStats['on_time'] += $cnt;
            if ($tmg === 'Late') $darsStats['late'] += $cnt;
        } elseif ($att === 'Absent') {
            $darsStats['absent'] += $cnt;
        }
    }
}
$darsAttendanceRate = $darsStats['total'] > 0 ? round(($darsStats['present'] / $darsStats['total']) * 100) : 0;

// 6. QURAN ATTENDANCE METRICS
$quranDateCond = getDateCondition('attendance_date', $period);
$quranSql = "SELECT status, COUNT(*) as cnt 
             FROM quran_attendance 
             WHERE user_id = '$userId' AND $quranDateCond 
             GROUP BY status";
$quranRes = mysqli_query($conn, $quranSql);

$quranStats = [
    'total' => 0,
    'present' => 0,
    'absent' => 0
];

if ($quranRes) {
    while ($qRow = mysqli_fetch_assoc($quranRes)) {
        $st = ucfirst(strtolower($qRow['status']));
        $cnt = intval($qRow['cnt']);
        $quranStats['total'] += $cnt;
        if ($st === 'Present') $quranStats['present'] += $cnt;
        if ($st === 'Absent') $quranStats['absent'] += $cnt;
    }
}
$quranRate = $quranStats['total'] > 0 ? round(($quranStats['present'] / $quranStats['total']) * 100) : 0;

// 7. DAWAH RECORDS
$dawahDateCond = getDateCondition('dawah_date', $period);
$dawahSql = "SELECT dawah_type, dawah_mode, COUNT(*) as cnt 
             FROM dawah_records 
             WHERE user_id = '$userId' AND $dawahDateCond 
             GROUP BY dawah_type, dawah_mode";
$dawahRes = mysqli_query($conn, $dawahSql);

$dawahStats = [
    'total' => 0,
    'personal' => 0,
    'collective' => 0,
    'online' => 0,
    'physical' => 0
];

if ($dawahRes) {
    while ($dwRow = mysqli_fetch_assoc($dawahRes)) {
        $type = ucfirst(strtolower($dwRow['dawah_type']));
        $mode = ucfirst(strtolower($dwRow['dawah_mode']));
        $cnt = intval($dwRow['cnt']);
        $dawahStats['total'] += $cnt;
        if ($type === 'Personal') $dawahStats['personal'] += $cnt;
        if ($type === 'Collective') $dawahStats['collective'] += $cnt;
        if ($mode === 'Online') $dawahStats['online'] += $cnt;
        if ($mode === 'Physical') $dawahStats['physical'] += $cnt;
    }
}

// 8. TIME TRACKER / SESSIONS
$timeDateCond = getDateCondition('start_time', $period);
$timeSql = "SELECT SUM(duration_minutes) as total_mins, COUNT(*) as total_sessions 
            FROM time_sessions 
            WHERE user_id = '$userId' AND duration_minutes > 0 AND $timeDateCond";
$timeRes = mysqli_query($conn, $timeSql);
$timeStats = [
    'total_minutes' => 0,
    'formatted_time' => '0m',
    'total_sessions' => 0
];
if ($timeRes && $tRow = mysqli_fetch_assoc($timeRes)) {
    $mins = round(floatval($tRow['total_mins'] ?? 0));
    $hrs = floor($mins / 60);
    $remMins = $mins % 60;
    $timeStats['total_minutes'] = $mins;
    $timeStats['total_sessions'] = intval($tRow['total_sessions'] ?? 0);
    $timeStats['formatted_time'] = ($hrs > 0 ? "{$hrs}h " : "") . "{$remMins}m";
}

// 9. OVERALL PERFORMANCE SCORE (0 - 100)
// Combine active metrics with weighted distribution
$scoreComponents = [];
$weights = [];

// Namaz (Weight: 35%)
if ($namazStats['total'] > 0) {
    $scoreComponents[] = $namazOfferingRate * 0.5 + $namazJamaatRate * 0.5;
    $weights[] = 35;
}

// Dars (Weight: 25%)
if ($darsStats['total'] > 0) {
    $scoreComponents[] = $darsAttendanceRate;
    $weights[] = 25;
}

// Quran (Weight: 20%)
if ($quranStats['total'] > 0) {
    $scoreComponents[] = $quranRate;
    $weights[] = 20;
}

// Tasks (Weight: 20%)
if ($totalAssigned > 0) {
    $scoreComponents[] = $taskCompletionRate;
    $weights[] = 20;
}

$overallScore = 0;
$totalWeight = array_sum($weights);
if ($totalWeight > 0) {
    $weightedSum = 0;
    for ($i = 0; $i < count($scoreComponents); $i++) {
        $weightedSum += ($scoreComponents[$i] * $weights[$i]);
    }
    $overallScore = round($weightedSum / $totalWeight);
} else {
    // If no activities recorded yet
    $overallScore = 0;
}

$scoreGrade = 'Needs Attention';
$scoreColor = '#ef4444';
if ($overallScore >= 85) {
    $scoreGrade = 'Excellent';
    $scoreColor = '#10b981';
} elseif ($overallScore >= 70) {
    $scoreGrade = 'Very Good';
    $scoreColor = '#3b82f6';
} elseif ($overallScore >= 50) {
    $scoreGrade = 'Good';
    $scoreColor = '#f59e0b';
}

$response = [
    'success' => true,
    'period' => $period,
    'user' => $user,
    'overall' => [
        'score' => $overallScore,
        'grade' => $scoreGrade,
        'color' => $scoreColor
    ],
    'tasks' => [
        'assigned_count' => $totalAssigned,
        'submitted_count' => $submittedCount,
        'approved_count' => $approvedCount,
        'pending_count' => $pendingCount,
        'late_count' => $lateCount,
        'completion_rate' => $taskCompletionRate,
        'submissions' => $comprehensiveTasks
    ],
    'namaz' => [
        'total' => $namazStats['total'],
        'with_jamaat' => $namazStats['with_jamaat'],
        'without_jamaat' => $namazStats['without_jamaat'],
        'missed' => $namazStats['missed'],
        'jamaat_rate' => $namazJamaatRate,
        'offering_rate' => $namazOfferingRate,
        'prayers' => $namazStats['prayers']
    ],
    'dars' => [
        'total' => $darsStats['total'],
        'present' => $darsStats['present'],
        'absent' => $darsStats['absent'],
        'on_time' => $darsStats['on_time'],
        'late' => $darsStats['late'],
        'attendance_rate' => $darsAttendanceRate
    ],
    'quran' => [
        'total' => $quranStats['total'],
        'present' => $quranStats['present'],
        'absent' => $quranStats['absent'],
        'rate' => $quranRate
    ],
    'dawah' => [
        'total' => $dawahStats['total'],
        'personal' => $dawahStats['personal'],
        'collective' => $dawahStats['collective'],
        'online' => $dawahStats['online'],
        'physical' => $dawahStats['physical']
    ],
    'time_tracker' => [
        'total_minutes' => $timeStats['total_minutes'],
        'formatted_time' => $timeStats['formatted_time'],
        'total_sessions' => $timeStats['total_sessions']
    ]
];

echo json_encode($response);
?>
