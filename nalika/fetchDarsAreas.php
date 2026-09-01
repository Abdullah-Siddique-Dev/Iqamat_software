<?php
include "connection.php";
session_start();
if (!isset($_SESSION['user'])) { exit("Unauthorized"); }

header('Content-Type: application/json');

$result = $conn->query("
    SELECT
        da.id,
        da.areaName,
        da.darsType,
        da.dayTime,
        da.startDate,
        da.location,
        da.mapLink,
        da.representative_id,
        CONCAT(u.firstName, ' ', u.lastName) AS representative,
        u.phone                              AS representativePhone,
        da.contactName,
        da.contactPhone
    FROM      dars_areas da
    LEFT JOIN users u ON u.id = da.representative_id
    ORDER BY  da.id DESC
");

$areas = [];
while ($row = $result->fetch_assoc()) {
    $row['id']                = (int) $row['id'];
    $row['representative_id'] = (int) $row['representative_id'];
    $areas[] = $row;
}

echo json_encode($areas);
$conn->close();
?>