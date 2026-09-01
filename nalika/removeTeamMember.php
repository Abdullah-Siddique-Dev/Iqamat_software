<?php
/**
 * removeTeamMember.php
 * POST  { team_id, user_id }
 * ── Blocks removal if the user is the current team leader
 * ── Returns { success, message }
 */
header('Content-Type: application/json');
include 'connection.php';
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$teamId = (int)($_POST['team_id'] ?? 0);
$userId = (int)($_POST['user_id'] ?? 0);

if (!$teamId || !$userId) {
    echo json_encode(['success' => false, 'message' => 'Missing team_id or user_id.']);
    exit;
}

// ── Guard: is this user the current leader? ────────────────────────────────
$chk = $conn->prepare("SELECT leader_id FROM teams WHERE id = ? LIMIT 1");
$chk->bind_param("i", $teamId);
$chk->execute();
$chk->bind_result($currentLeaderId);
$chk->fetch();
$chk->close();

if ((int)$currentLeaderId === $userId) {
    echo json_encode([
        'success' => false,
        'message' => 'Cannot remove the team leader. Please change the leader first.',
    ]);
    exit;
}

// ── Delete ─────────────────────────────────────────────────────────────────
$del = $conn->prepare("
    DELETE FROM team_members WHERE team_id = ? AND user_id = ?
");
$del->bind_param("ii", $teamId, $userId);

if ($del->execute() && $del->affected_rows > 0) {
    $del->close();
    echo json_encode(['success' => true, 'message' => 'Member removed from team.']);
} else {
    $del->close();
    echo json_encode(['success' => false, 'message' => 'Member not found in this team.']);
}