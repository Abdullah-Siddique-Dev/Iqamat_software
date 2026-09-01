<?php
/**
 * addTeamMember.php
 * POST  { team_id, user_id, team_role }
 * ── Inserts into team_members; UNIQUE(team_id, user_id) prevents dupes
 * ── Returns { success, message }
 */
header('Content-Type: application/json');
include 'connection.php';
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$teamId   = (int)($_POST['team_id']   ?? 0);
$userId   = (int)($_POST['user_id']   ?? 0);
$teamRole = trim($_POST['team_role']  ?? 'member');

if (!$teamId || !$userId) {
    echo json_encode(['success' => false, 'message' => 'Missing team_id or user_id.']);
    exit;
}

// ── Check if user is already in this team ──────────────────────────────────
$chk = $conn->prepare("
    SELECT id FROM team_members WHERE team_id = ? AND user_id = ? LIMIT 1
");
$chk->bind_param("ii", $teamId, $userId);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    $chk->close();
    echo json_encode(['success' => false, 'message' => 'User is already in this team.']);
    exit;
}
$chk->close();

// ── Insert ─────────────────────────────────────────────────────────────────
$ins = $conn->prepare("
    INSERT INTO team_members (team_id, user_id, team_role) VALUES (?, ?, ?)
");
$ins->bind_param("iis", $teamId, $userId, $teamRole);

if ($ins->execute()) {
    $ins->close();
    echo json_encode(['success' => true, 'message' => 'Member added to team successfully.']);
} else {
    $ins->close();
    echo json_encode(['success' => false, 'message' => 'Failed to add member.']);
}