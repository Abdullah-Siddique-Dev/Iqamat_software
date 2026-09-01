<?php
// updateDawah.php
header('Content-Type: application/json');
ob_start();

// ── Database Connection ──
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . "/connection.php";

// ── Read JSON body ──
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
    exit;
}

$id             = isset($data['id'])             ? (int)$data['id']             : 0;
$user_id        = isset($data['user_id'])        ? (int)$data['user_id']        : 0;
$dawah_type     = isset($data['dawah_type'])     ? trim($data['dawah_type'])     : '';
$dawah_mode     = isset($data['dawah_mode'])     ? trim($data['dawah_mode'])     : 'Physical';
$person_name    = isset($data['person_name'])    ? trim($data['person_name'])    : '';
$person_contact = isset($data['person_contact']) ? trim($data['person_contact']) : '';
$location       = isset($data['location'])       ? trim($data['location'])       : '';
$dawah_date     = isset($data['dawah_date'])     ? trim($data['dawah_date'])     : '';
$remarks        = isset($data['remarks'])        ? trim($data['remarks'])        : '';

// ── Validate ──
if (!$id) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Missing record ID.']);
    exit;
}
if (!$user_id) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Missing user ID.']);
    exit;
}
if (!$person_name) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Person name is required.']);
    exit;
}
if (!$location) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Location is required.']);
    exit;
}
if (!$dawah_date) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Date is required.']);
    exit;
}

// Collective always Physical
if ($dawah_type === 'Collective') {
    $dawah_mode = 'Physical';
}

// ── Update ──
$stmt = $conn->prepare(
    "UPDATE dawah_records
     SET dawah_type     = ?,
         dawah_mode     = ?,
         person_name    = ?,
         person_contact = ?,
         location       = ?,
         dawah_date     = ?,
         remarks        = ?
     WHERE id = ? AND user_id = ?"
);

$stmt->bind_param(
    "sssssssii",
    $dawah_type,
    $dawah_mode,
    $person_name,
    $person_contact,
    $location,
    $dawah_date,
    $remarks,
    $id,
    $user_id
);

$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();
$conn->close();

ob_clean();
if ($affected === 0) {
    // Could be nothing changed (same data) — still a success
    echo json_encode(['success' => true, 'message' => 'No changes detected.']);
} else {
    echo json_encode(['success' => true, 'message' => 'Record updated.']);
}