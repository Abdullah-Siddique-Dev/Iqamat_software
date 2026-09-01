<?php
// addEventDars.php
header('Content-Type: text/plain');
include "connection.php";

// Get POST data
$topic = $_POST['topic'] ?? null;
$organisier = $_POST['organisier'] ?? null;
$dateTime = $_POST['dateTime'] ?? null;
$phone = $_POST['phone'] ?? null;
$location = $_POST['location'] ?? null;

// ✅ Set type as constant
$type = "Workshop";

if (!$topic || !$organisier) {
    echo "❌ Missing required fields";
    exit;
}

// Convert datetime-local format if necessary
$dateTime = $dateTime ? str_replace('T', ' ', $dateTime) : null;

try {
    $stmt = $conn->prepare("
        INSERT INTO events (topic, organisier, dateTime, phone, location, type)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("ssssss", $topic, $organisier, $dateTime, $phone, $location, $type);

    if ($stmt->execute()) {
        echo "✅ Event Dars added successfully!";
    } else {
        echo "❌ Insert failed: " . $stmt->error;
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage();
}

$stmt->close();
$conn->close();
?>