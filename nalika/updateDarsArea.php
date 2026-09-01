<?php
include "connection.php";
session_start();
if (!isset($_SESSION['user'])) { exit("Unauthorized"); }

$id                = intval($_POST['id']                ?? 0);
$areaName          = trim($_POST['areaName']            ?? '');
$darsType          = trim($_POST['darsType']            ?? '');
$dayTime           = trim($_POST['dayTime']             ?? '');
$startDate         = trim($_POST['startDate']           ?? '') ?: null;
$location          = trim($_POST['location']            ?? '');
$mapLink           = trim($_POST['mapLink']             ?? '');
$representative_id = intval($_POST['representative_id'] ?? 0) ?: null;  // FK → users.id
$contactName       = trim($_POST['contactName']         ?? '');
$contactPhone      = trim($_POST['contactPhone']        ?? '');

if (!$id || !$areaName || !$darsType) {
    echo "Area Name and Dars Type are required.";
    exit;
}

$stmt = $conn->prepare("
    UPDATE dars_areas SET
        areaName          = ?,
        darsType          = ?,
        dayTime           = ?,
        startDate         = ?,
        location          = ?,
        mapLink           = ?,
        representative_id = ?,
        contactName       = ?,
        contactPhone      = ?
    WHERE id = ?
");
$stmt->bind_param("ssssssissi",
    $areaName, $darsType, $dayTime, $startDate,
    $location, $mapLink, $representative_id, $contactName, $contactPhone,
    $id
);

if ($stmt->execute()) {
    echo "Dars Area updated successfully!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>