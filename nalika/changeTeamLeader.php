<?php
/**
 * changeTeamLeader.php
 * POST  { team_id, leader_id }
 * ── Updates teams.leader_id
 * ── If new leader is not yet in team_members → auto-adds them
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
$leaderId = (int)($_POST['leader_id'] ?? 0);

if (!$teamId || !$leaderId) {
    echo json_encode(['success' => false, 'message' => 'Missing team_id or leader_id.']);
    exit;
}

$conn->begin_transaction();
try {
    // 1. Update team leader
    $upd = $conn->prepare("UPDATE teams SET leader_id = ? WHERE id = ?");
    $upd->bind_param("ii", $leaderId, $teamId);
    $upd->execute();
    $upd->close();

    // 2. Check if new leader is already a member
    $chk = $conn->prepare("
        SELECT id FROM team_members WHERE team_id = ? AND user_id = ? LIMIT 1
    ");
    $chk->bind_param("ii", $teamId, $leaderId);
    $chk->execute();
    $chk->store_result();
    $alreadyIn = $chk->num_rows > 0;
    $chk->close();

    // 3. Auto-add if not in team
    $autoAdded = false;
    if (!$alreadyIn) {
        $am = $conn->prepare("
            INSERT INTO team_members (team_id, user_id, team_role) VALUES (?, ?, 'leader')
        ");
        $am->bind_param("ii", $teamId, $leaderId);
        $am->execute();
        $am->close();
        $autoAdded = true;
    }

    $conn->commit();

    $msg = 'Team leader updated successfully.';
    if ($autoAdded) $msg .= ' The new leader was also added to the team.';

    echo json_encode(['success' => true, 'message' => $msg]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}