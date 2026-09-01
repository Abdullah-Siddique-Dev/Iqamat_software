<?php
/**
 * fetchTimeData.php
 * GET ?user_id=&from=YYYY-MM-DD&to=YYYY-MM-DD
 */

// ── Catch ALL errors and return JSON, never HTML ──────────────────
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

// ── Suppress any output before us (BOM, whitespace, etc.) ─────────
ob_start();

include 'connection.php';
include 'auth.php';

ob_clean(); // discard any noise printed by included files

date_default_timezone_set('Asia/Karachi');

$userId = (int)($_GET['user_id'] ?? $loggedUserId ?? 0);
$from   = $_GET['from'] ?? date('Y-m-d');
$to     = $_GET['to']   ?? date('Y-m-d');

if (!$userId ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // ── Detect PDO vs mysqli ──────────────────────────────────────
    if (isset($pdo) && $pdo instanceof PDO) {

        // ── 1. Daily totals ──────────────────────────────────────
        $stmt = $pdo->prepare("
            SELECT session_date AS `date`,
                   COALESCE(SUM(duration_minutes), 0) AS total_minutes,
                   IF(SUM(duration_minutes) >= 60, 1, 0) AS goal_achieved
              FROM time_sessions
             WHERE user_id = :uid
               AND session_date BETWEEN :from AND :to
               AND end_time IS NOT NULL
             GROUP BY session_date
        ");
        $stmt->execute([':uid' => $userId, ':from' => $from, ':to' => $to]);
        $days = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── 2. Open session ──────────────────────────────────────
        $stmt2 = $pdo->prepare("
            SELECT id, start_time
              FROM time_sessions
             WHERE user_id = :uid AND end_time IS NULL
             ORDER BY start_time DESC LIMIT 1
        ");
        $stmt2->execute([':uid' => $userId]);
        $openSession = $stmt2->fetch(PDO::FETCH_ASSOC) ?: null;

    } elseif (isset($conn)) {
        // ── mysqli fallback ──────────────────────────────────────
        $userId_esc = (int)$userId;
        $from_esc   = $conn->real_escape_string($from);
        $to_esc     = $conn->real_escape_string($to);

        $result = $conn->query("
            SELECT session_date AS `date`,
                   COALESCE(SUM(duration_minutes), 0) AS total_minutes,
                   IF(SUM(duration_minutes) >= 60, 1, 0) AS goal_achieved
              FROM time_sessions
             WHERE user_id = $userId_esc
               AND session_date BETWEEN '$from_esc' AND '$to_esc'
               AND end_time IS NOT NULL
             GROUP BY session_date
        ");
        $days = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $result2 = $conn->query("
            SELECT id, start_time
              FROM time_sessions
             WHERE user_id = $userId_esc AND end_time IS NULL
             ORDER BY start_time DESC LIMIT 1
        ");
        $openSession = ($result2 && $result2->num_rows > 0)
                       ? $result2->fetch_assoc() : null;
    } else {
        echo json_encode(['success' => false, 'message' => 'No DB connection found']);
        exit;
    }

    // Cast types
    foreach ($days as &$d) {
        $d['total_minutes'] = (float)$d['total_minutes'];
        $d['goal_achieved'] = (bool)(int)$d['goal_achieved'];
    }
    unset($d);

    echo json_encode([
        'success'      => true,
        'days'         => $days,
        'open_session' => $openSession,
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>