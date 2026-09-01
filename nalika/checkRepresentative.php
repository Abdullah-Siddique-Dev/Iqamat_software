<?php

include 'connection.php';   // Your database connection

$area = $_GET['area'];

$sql = "SELECT COUNT(*) AS total
        FROM users
        WHERE area = ?
        AND role = 'representative'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $area);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode([
    "hasRepresentative" => ($row['total'] > 0)
]);