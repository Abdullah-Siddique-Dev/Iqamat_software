<?php
/**
 * fetchTeams.php
 * GET  → JSON array of all teams with leader info
 * [{ id, name, leader_id, leader_name }, …]
 */
header('Content-Type: application/json');
include 'connection.php';
include 'auth.php';

try {
    $stmt = $conn->prepare("
        SELECT  t.id,
                t.name,
                t.leader_id,
                CONCAT(u.firstName, ' ', u.lastName) AS leader_name
        FROM    teams t
        LEFT    JOIN users u ON u.id = t.leader_id
        ORDER   BY t.name ASC
    ");
    $stmt->execute();
    $teams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Cast id / leader_id to int
    foreach ($teams as &$t) {
        $t['id']        = (int)$t['id'];
        $t['leader_id'] = (int)$t['leader_id'];
    }

    echo json_encode($teams);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([]);
}