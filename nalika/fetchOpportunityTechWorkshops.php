<?php
include "connection.php";
session_start();
if (!isset($_SESSION['user'])) { exit("Unauthorized"); }

header('Content-Type: application/json');

$result = $conn->query("
    SELECT id, topic, skills, organisier, dateTime,
           durationValue, durationUnit, frequency,
           phone, location, feeType, feeAmount
    FROM technical_workshops
    ORDER BY dateTime ASC
");

$workshops = [];
while ($row = $result->fetch_assoc()) {
    $workshops[] = $row;
}

echo json_encode($workshops);
$conn->close();
?>