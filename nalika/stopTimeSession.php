<?php
/**
 * stopTimeSession.php
 * POST { user_id, session_id }
 * Response: { success, duration_minutes, daily_total, goal_achieved }
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

ob_start();
include 'connection.php';
include 'auth.php';
ob_clean();

// Set default timezone for consistent time calculations
date_default_timezone_set('Asia/Karachi');

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$userId    = (int)($body['user_id']    ?? $loggedUserId ?? 0);
$sessionId = (int)($body['session_id'] ?? 0);

if (!$userId || !$sessionId) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id or session_id']);
    exit;
}

$now     = new DateTime();
$endTime = $now->format('Y-m-d H:i:s');
$today   = $now->format('Y-m-d');

try {

    if (isset($pdo) && $pdo instanceof PDO) {

        // ── 1. Close the session, compute duration ─────────────────
        $upd = $pdo->prepare("
            UPDATE time_sessions
               SET end_time         = :end,
                   duration_minutes = GREATEST(
                       ROUND(TIMESTAMPDIFF(SECOND, start_time, :end2) / 60, 2),
                       0
                   )
              WHERE id      = :sid
                AND user_id = :uid
                AND end_time IS NULL
        ");
        $upd->execute([
            ':end'  => $endTime,
            ':end2' => $endTime,
            ':sid'  => $sessionId,
            ':uid'  => $userId,
        ]);

        // ── 2. Get this session's duration ────────────────────────
        $dur = $pdo->prepare("
            SELECT duration_minutes FROM time_sessions
             WHERE id = :sid AND user_id = :uid
        ");
        $dur->execute([':sid' => $sessionId, ':uid' => $userId]);
        $durMins = (float)($dur->fetchColumn() ?? 0);

        // ── 3. Daily total ────────────────────────────────────────
        $tot = $pdo->prepare("
            SELECT COALESCE(SUM(duration_minutes), 0)
              FROM time_sessions
             WHERE user_id      = :uid
               AND session_date = :today
               AND end_time IS NOT NULL
        ");
        $tot->execute([':uid' => $userId, ':today' => $today]);
        $dailyTotal = (float)$tot->fetchColumn();

        // ── 4. Upsert daily summary ───────────────────────────────
        $pdo->prepare("
            INSERT INTO time_daily (user_id, log_date, total_minutes, goal_achieved)
            VALUES (:uid, :date, :total, :goal)
            ON DUPLICATE KEY UPDATE
                total_minutes = :total2,
                goal_achieved = :goal2
        ")->execute([
            ':uid'   => $userId,
            ':date'  => $today,
            ':total' => $dailyTotal,
            ':goal'  => $dailyTotal >= 60 ? 1 : 0,
            ':total2'=> $dailyTotal,
            ':goal2' => $dailyTotal >= 60 ? 1 : 0,
        ]);

    } elseif (isset($conn)) {

        $uid  = (int)$userId;
        $sid  = (int)$sessionId;
        $et   = $conn->real_escape_string($endTime);
        $td   = $conn->real_escape_string($today);

        $conn->query("
            UPDATE time_sessions
               SET end_time = '$et',
                   duration_minutes = GREATEST(
                       ROUND(TIMESTAMPDIFF(SECOND, start_time, '$et') / 60, 2), 0
                   )
             WHERE id = $sid AND user_id = $uid AND end_time IS NULL
        ");

        $r = $conn->query("
            SELECT duration_minutes FROM time_sessions WHERE id = $sid AND user_id = $uid
        ");
        $durMins = $r ? (float)$r->fetch_assoc()['duration_minutes'] : 0;

        $r2 = $conn->query("
            SELECT COALESCE(SUM(duration_minutes), 0) AS tot
              FROM time_sessions
             WHERE user_id = $uid AND session_date = '$td' AND end_time IS NOT NULL
        ");
        $dailyTotal = $r2 ? (float)$r2->fetch_assoc()['tot'] : 0;

        $goal = $dailyTotal >= 60 ? 1 : 0;
        $conn->query("
            INSERT INTO time_daily (user_id, log_date, total_minutes, goal_achieved)
            VALUES ($uid, '$td', $dailyTotal, $goal)
            ON DUPLICATE KEY UPDATE
                total_minutes = $dailyTotal,
                goal_achieved = $goal
        ");

    } else {
        echo json_encode(['success' => false, 'message' => 'No DB connection']);
        exit;
    }

    echo json_encode([
        'success'          => true,
        'duration_minutes' => round($durMins, 2),
        'daily_total'      => round($dailyTotal, 2),
        'goal_achieved'    => $dailyTotal >= 60,
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>