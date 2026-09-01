<?php
// updateEventDars.php

header('Content-Type: text/plain'); // We'll return a simple message
include "connection.php";

// Validate required POST parameters
$id = $_POST['id'] ?? null;
$topic = $_POST['topic'] ?? null;
$organisier = $_POST['organisier'] ?? null;
$dateTime = $_POST['dateTime'] ?? null;
$phone = $_POST['phone'] ?? null;
$location = $_POST['location'] ?? null;

if (!$id || !$topic || !$organisier) {
    echo "❌ Missing required fields";
    exit;
}

try {
    // Prepare update statement
    $stmt = $conn->prepare("
        UPDATE events 
        SET topic = ?, organisier = ?, dateTime = ?, phone = ?, location = ?
        WHERE id = ?
    ");

    // Bind parameters
    $stmt->bind_param(
        "sssssi",
        $topic,
        $organisier,
        $dateTime,
        $phone,
        $location,
        $id
    );

    if ($stmt->execute()) {
        echo "✅ Event updated successfully!";
    } else {
        echo "❌ Update failed: " . $stmt->error;
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage();
}

$stmt->close();
$conn->close();
?>