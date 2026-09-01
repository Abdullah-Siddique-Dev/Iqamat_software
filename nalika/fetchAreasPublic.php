<?php
include "connection.php";
header('Content-Type: application/json');

$result = $conn->query("SELECT id, areaName FROM dars_areas ORDER BY areaName ASC");
$areas = [];
while ($row = $result->fetch_assoc()) {
    $areas[] = $row;
}
echo json_encode($areas);
$conn->close();
?>