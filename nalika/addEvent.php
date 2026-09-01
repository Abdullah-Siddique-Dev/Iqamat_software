<?php
header('Content-Type: application/json');
include "connection.php";

$topic      = $_POST['topic']    ?? null;
$organisier = $_POST['organisier'] ?? null;
$dateTime   = $_POST['dateTime'] ?? null;
$phone      = $_POST['phone']    ?? null;
$location   = $_POST['location'] ?? null;
$type       = $_POST['type']     ?? "Dars";

if (!$topic || !$organisier) {
    echo json_encode(["success" => false, "message" => "❌ Missing required fields"]);
    exit;
}

$dateTime = $dateTime ? str_replace('T', ' ', $dateTime) : null;

try {
    $stmt = $conn->prepare("
        INSERT INTO events (topic, dateTime, phone, location, type, organisier)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssssi", $topic, $dateTime, $phone, $location, $type, $organisier);

    if ($stmt->execute()) {
        $eventId = $stmt->insert_id;

        // ✅ Fast — just DB writes, no email/network calls here anymore
        createEventNotifications($conn, $eventId, $organisier, $topic, $location, $type);

        echo json_encode([
            "success" => true,
            "message" => "✅ Event ($type) added successfully!",
            "eventId" => $eventId
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "❌ Insert failed: " . $stmt->error]);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "❌ Exception: " . $e->getMessage()]);
}

$conn->close();

// ── Helpers ──────────────────────────────────────────────────────────────────

function createEventNotifications($conn, $eventId, $organisierId, $topic, $location, $type) {

    $message = "New $type: \"$topic\" at $location";
    $isDars  = (strtolower($type) === 'dars');

    if ($isDars) {
        $areaStmt = $conn->prepare("SELECT area FROM users WHERE id = ? LIMIT 1");
        $areaStmt->bind_param("i", $organisierId);
        $areaStmt->execute();
        $areaRow = $areaStmt->get_result()->fetch_assoc();
        $areaStmt->close();

        if (!$areaRow || empty($areaRow['area'])) return;
        $area = $areaRow['area'];

        $usersStmt = $conn->prepare("SELECT id FROM users WHERE area = ? AND id != ? AND is_active = 1");
        $usersStmt->bind_param("si", $area, $organisierId);
    } else {
        $area      = null;
        $usersStmt = $conn->prepare("SELECT id FROM users WHERE id != ? AND is_active = 1");
        $usersStmt->bind_param("i", $organisierId);
    }

    $usersStmt->execute();
    $usersResult = $usersStmt->get_result();

    $notifStmt = $conn->prepare("
        INSERT INTO notifications (event_id, user_id, organisier_id, area, type, message)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    while ($user = $usersResult->fetch_assoc()) {
        $uid = $user['id'];
        $notifStmt->bind_param("iiisss", $eventId, $uid, $organisierId, $area, $type, $message);
        $notifStmt->execute();
    }

    $usersStmt->close();
    $notifStmt->close();
}
?>