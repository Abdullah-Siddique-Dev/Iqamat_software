<?php
/**
 * createTeam.php
 * POST  { name, leader_id }
 * ── Creates the team row and auto-adds leader to team_members
 * ── Returns { success, team_id, message }
 */
header('Content-Type: application/json');
include 'connection.php';
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$name     = trim($_POST['name']      ?? '');
$leaderId = (int)($_POST['leader_id'] ?? 0);

if (!$name) {
    echo json_encode(['success' => false, 'message' => 'Team name is required.']);
    exit;
}
if (!$leaderId) {
    echo json_encode(['success' => false, 'message' => 'Please select a leader.']);
    exit;
}

// ── Prevent duplicate team name ────────────────────────────────────────────
$chk = $conn->prepare("SELECT id FROM teams WHERE name = ? LIMIT 1");
$chk->bind_param("s", $name);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    $chk->close();
    echo json_encode(['success' => false, 'message' => "A team named \"{$name}\" already exists."]);
    exit;
}
$chk->close();

$conn->begin_transaction();
try {
    // 1. Insert team
    $ins = $conn->prepare("INSERT INTO teams (name, leader_id) VALUES (?, ?)");
    $ins->bind_param("si", $name, $leaderId);
    $ins->execute();
    $teamId = $conn->insert_id;
    $ins->close();

    // 2. Auto-add leader to team_members (ignore if somehow duplicate)
    $am = $conn->prepare("
        INSERT IGNORE INTO team_members (team_id, user_id, team_role)
        VALUES (?, ?, 'leader')
    ");
    $am->bind_param("ii", $teamId, $leaderId);
    $am->execute();
    $am->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'team_id' => $teamId,
        'message' => "Team \"{$name}\" created successfully.",
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}