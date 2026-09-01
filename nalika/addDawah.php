
<?php
// saveDawah.php
header('Content-Type: application/json');

ob_start();
include "connection.php";

$data = json_decode(file_get_contents('php://input'), true);

$user_id        = isset($data['user_id'])        ? (int)$data['user_id']              : 0;
$dawah_type     = isset($data['dawah_type'])     ? trim($data['dawah_type'])           : '';
$dawah_mode     = isset($data['dawah_mode'])     ? trim($data['dawah_mode'])           : 'Physical';
$person_name    = isset($data['person_name'])    ? trim($data['person_name'])          : '';
$person_contact = isset($data['person_contact']) ? trim($data['person_contact'])       : '';
$location       = isset($data['location'])       ? trim($data['location'])             : '';
$dawah_date     = isset($data['dawah_date'])     ? trim($data['dawah_date'])           : '';
$remarks        = isset($data['remarks'])        ? trim($data['remarks'])              : '';

// Validation
if (!$user_id || !$person_name || !$location || !$dawah_date) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// Collective always Physical
if ($dawah_type === 'Collective') $dawah_mode = 'Physical';

try {
    $stmt = $conn->prepare(
        "INSERT INTO dawah_records
            (user_id, dawah_type, dawah_mode, person_name, person_contact, location, dawah_date, remarks)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "isssssss",
        $user_id, $dawah_type, $dawah_mode,
        $person_name, $person_contact,
        $location, $dawah_date, $remarks
    );
    $stmt->execute();
    $newId = $conn->insert_id;

    ob_clean();
    echo json_encode(['success' => true, 'id' => $newId]);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();