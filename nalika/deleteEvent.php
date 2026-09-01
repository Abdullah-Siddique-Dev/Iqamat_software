<?php
// deleteEventDars.php

include "connection.php";

// Make sure an ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Invalid ID";
    exit;
}

$id = intval($_GET['id']); // sanitize input

try {
    // Prepare DELETE query
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo "Event deleted successfully!";
    } else {
        echo "Error deleting Event: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
?>