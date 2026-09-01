<?php
header('Content-Type: application/json');
include "connection.php";

$type = $_GET['type'] ?? null;

$sql = "
    SELECT 
        e.id,
        e.topic,
        e.dateTime,
        e.phone,
        e.location,
        e.type,
        e.organisier,                                    -- ID, still needed for edit form
        CONCAT(u.firstName, ' ', u.lastName) AS organisierName  -- name, for display
    FROM events e
    LEFT JOIN users u ON e.organisier = u.id
    WHERE e.type = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $type);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

echo json_encode($events);
$stmt->close();
$conn->close();
?>