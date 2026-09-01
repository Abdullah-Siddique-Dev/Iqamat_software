<?php
/**
 * deleteTeam.php
 * POST  { team_id }
 * ── Deletes team + all team_members rows (cascade or manual)
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
if (!$teamId) {
    echo json_encode(['success' => false, 'message' => 'Missing team_id.']);
    exit;
}

$conn->begin_transaction();
try {
    // 1. Delete all members first (in case FK cascade not set up)
    $dm = $conn->prepare("DELETE FROM team_members WHERE team_id = ?");
    $dm->bind_param("i", $teamId);
    $dm->execute();
    $dm->close();

    // 2. Delete team
    $dt = $conn->prepare("DELETE FROM teams WHERE id = ?");
    $dt->bind_param("i", $teamId);
    $dt->execute();
    $affected = $dt->affected_rows;
    $dt->close();

    $conn->commit();

    if ($affected > 0) {
        echo json_encode(['success' => true, 'message' => 'Team deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Team not found.']);
    }

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}