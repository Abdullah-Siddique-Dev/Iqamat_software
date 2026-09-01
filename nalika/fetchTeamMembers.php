<?php
/**
 * fetchTeamMembers.php
 * GET  ?team_id=X
 * → { leader_name, members: [{user_id, firstName, lastName, username, role, team_role, is_leader}, …] }
 */
header('Content-Type: application/json');
include 'connection.php';
include 'auth.php';

$teamId = (int)($_GET['team_id'] ?? 0);
if (!$teamId) {
    echo json_encode(['leader_name' => null, 'members' => []]);
    exit;
}

// ── Get team leader name ───────────────────────────────────────────────────
$tStmt = $conn->prepare("
    SELECT  t.leader_id,
            CONCAT(u.firstName, ' ', u.lastName) AS leader_name
    FROM    teams t
    LEFT    JOIN users u ON u.id = t.leader_id
    WHERE   t.id = ?
    LIMIT   1
");
$tStmt->bind_param("i", $teamId);
$tStmt->execute();
$teamRow = $tStmt->get_result()->fetch_assoc();
$tStmt->close();

$leaderId   = (int)($teamRow['leader_id'] ?? 0);
$leaderName = $teamRow['leader_name'] ?? null;

// ── Get all members ────────────────────────────────────────────────────────
$mStmt = $conn->prepare("
    SELECT  tm.user_id,
            u.firstName,
            u.lastName,
            u.username,
            u.role,
            tm.team_role,
            IF(u.id = ?, 1, 0) AS is_leader
    FROM    team_members tm
    JOIN    users u ON u.id = tm.user_id
    WHERE   tm.team_id = ?
    ORDER   BY is_leader DESC, u.firstName, u.lastName
");
$mStmt->bind_param("ii", $leaderId, $teamId);
$mStmt->execute();
$members = $mStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$mStmt->close();

foreach ($members as &$m) {
    $m['user_id']   = (int)$m['user_id'];
    $m['is_leader'] = (bool)$m['is_leader'];
}

echo json_encode([
    'leader_name' => $leaderName,
    'members'     => $members,
]);