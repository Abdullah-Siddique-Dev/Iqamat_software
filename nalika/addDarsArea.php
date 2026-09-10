<?php
include "connection.php";
session_start();
if (!isset($_SESSION['user'])) { exit("Unauthorized"); }

$areaName          = trim($_POST['areaName']            ?? '');
$darsType          = trim($_POST['darsType']            ?? '');
$dayTime           = trim($_POST['dayTime']             ?? '');
$startDate         = trim($_POST['startDate']           ?? '') ?: null;
$location          = trim($_POST['location']            ?? '');
$mapLink           = trim($_POST['mapLink']             ?? '');
$representative_id = intval($_POST['representative_id'] ?? 0) ?: null;  // FK → users.id
$contactName       = trim($_POST['contactName']         ?? '');
$contactPhone      = trim($_POST['contactPhone']        ?? '');

if (!$areaName || !$darsType) {
    echo "Area Name and Dars Type are required.";
    exit;
}

if (preg_match('/[0-9]/', $areaName)) {
    echo "Error: Only letters allowed for Area Name. No digits permitted.";
    exit;
}

if (!empty($contactName) && preg_match('/[0-9]/', $contactName)) {
    echo "Error: Only letters allowed for Contact Name. No digits permitted.";
    exit;
}

if (!empty($contactPhone) && preg_match('/[a-zA-Z]/', $contactPhone)) {
    echo "Error: Only numbers allowed for Contact Phone. No letters permitted.";
    exit;
}

// Type string: s s s s s s i s s  (representative_id is integer)
$stmt = $conn->prepare("
    INSERT INTO dars_areas
        (areaName, darsType, dayTime, startDate, location, mapLink, representative_id, contactName, contactPhone)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "ssssssiss",
    $areaName,
    $darsType,
    $dayTime,
    $startDate,
    $location,
    $mapLink,
    $representative_id,
    $contactName,
    $contactPhone
);

if ($stmt->execute()) {
    echo "Dars Area added successfully!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>