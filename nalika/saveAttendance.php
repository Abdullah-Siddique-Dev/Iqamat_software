<?php
include "connection.php";
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$area_id      = intval($input['area_id']      ?? 0);
$submitted_by = intval($input['submitted_by'] ?? 0);
$dateTime     = trim($input['dateTime']       ?? '');
$note         = trim($input['note']           ?? '');
$records      = $input['records']             ?? [];

if (!$area_id || !$submitted_by || !$dateTime || empty($records)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

// Insert one row per member into dars_attendance
$stmt = $conn->prepare("
    INSERT INTO dars_attendance
        (area_id, user_id, submitted_by, dateTime, attendance, timing, note)
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        attendance   = VALUES(attendance),
        timing       = VALUES(timing),
        note         = VALUES(note),
        submitted_by = VALUES(submitted_by)
");

$savedCount = 0;
$errors     = [];

foreach ($records as $rec) {
    $user_id    = intval($rec['user_id']    ?? 0);
    $attendance = in_array($rec['attendance'], ['Present', 'Absent']) ? $rec['attendance'] : 'Absent';
    $timing     = in_array($rec['timing'],     ['OnTime',  'Late'])   ? $rec['timing']     : 'OnTime';

    if (!$user_id) continue;

    $stmt->bind_param(
        "iiissss",
        $area_id,
        $user_id,
        $submitted_by,
        $dateTime,
        $attendance,
        $timing,
        $note
    );

    if ($stmt->execute()) {
        $savedCount++;
    } else {
        $errors[] = "User {$user_id}: " . $stmt->error;
    }
}

$stmt->close();
$conn->close();

if ($savedCount > 0) {
    echo json_encode([
        'success' => true,
        'message' => "Attendance saved for {$savedCount} member(s).",
        'errors'  => $errors,
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No records were saved.',
        'errors'  => $errors,
    ]);
}
?>