<?php
/**
 * startTimeSession.php
 * POST { user_id, area_id }
 * Response: { success, session_id, start_time, start_time_iso }
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

// ── Read JSON body ────────────────────────────────────────────────
$body    = json_decode(file_get_contents('php://input'), true) ?? [];
$userId  = (int)($body['user_id']  ?? $loggedUserId ?? 0);
$areaId  = (int)($body['area_id']  ?? $loggedArea   ?? 0);

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

$now          = new DateTime();
$startTime    = $now->format('Y-m-d H:i:s');
$startTimeIso = $now->format('c');
$sessionDate  = $now->format('Y-m-d');

try {

    if (isset($pdo) && $pdo instanceof PDO) {

        // ── Close any accidentally open sessions first ─────────────
        $pdo->prepare("
            UPDATE time_sessions
               SET end_time = :now,
                   duration_minutes = TIMESTAMPDIFF(SECOND, start_time, :now2) / 60
             WHERE user_id = :uid AND end_time IS NULL
        ")->execute([':now' => $startTime, ':now2' => $startTime, ':uid' => $userId]);

        // ── Insert new session ─────────────────────────────────────
        $ins = $pdo->prepare("
            INSERT INTO time_sessions (user_id, area_id, session_date, start_time)
            VALUES (:uid, :aid, :sdate, :stime)
        ");
        $ins->execute([
            ':uid'   => $userId,
            ':aid'   => $areaId ?: null,
            ':sdate' => $sessionDate,
            ':stime' => $startTime,
        ]);
        $sessionId = (int)$pdo->lastInsertId();

    } elseif (isset($conn)) {

        $uid   = (int)$userId;
        $aid   = (int)$areaId;
        $st    = $conn->real_escape_string($startTime);
        $sd    = $conn->real_escape_string($sessionDate);

        // Close stale open sessions
        $conn->query("
            UPDATE time_sessions
               SET end_time = '$st',
                   duration_minutes = TIMESTAMPDIFF(SECOND, start_time, '$st') / 60
             WHERE user_id = $uid AND end_time IS NULL
        ");

        $conn->query("
            INSERT INTO time_sessions (user_id, area_id, session_date, start_time)
            VALUES ($uid, " . ($aid ?: 'NULL') . ", '$sd', '$st')
        ");
        $sessionId = (int)$conn->insert_id;

    } else {
        echo json_encode(['success' => false, 'message' => 'No DB connection']);
        exit;
    }

    echo json_encode([
        'success'        => true,
        'session_id'     => $sessionId,
        'start_time'     => $startTime,
        'start_time_iso' => $startTimeIso,
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>