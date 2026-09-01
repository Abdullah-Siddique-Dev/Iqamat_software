<?php
include "connection.php";
session_start();
if (!isset($_SESSION['user'])) { exit("Unauthorized"); }

$topic         = trim($_POST['topic']         ?? '');
$skills        = trim($_POST['skills']        ?? '');
$organisier    = trim($_POST['organisier']    ?? '');
$dateTime      = trim($_POST['dateTime']      ?? '') ?: null;
$durationValue = intval($_POST['durationValue'] ?? 0) ?: null;
$durationUnit  = trim($_POST['durationUnit']  ?? 'Months');
$frequency     = intval($_POST['frequency']   ?? 0) ?: null;
$phone         = trim($_POST['phone']         ?? '');
$location      = trim($_POST['location']      ?? '');
$feeType       = trim($_POST['feeType']       ?? 'Free');
$feeAmount     = $feeType === 'Paid' ? trim($_POST['feeAmount'] ?? '') : null;

if (!$topic || !$skills || !$organisier) {
    echo "Topic, Skills and Organiser are required.";
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO technical_workshops
        (topic, skills, organisier, dateTime, durationValue, durationUnit, frequency, phone, location, feeType, feeAmount)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("ssssissssss",
    $topic, $skills, $organisier, $dateTime,
    $durationValue, $durationUnit, $frequency,
    $phone, $location, $feeType, $feeAmount
);

if ($stmt->execute()) {
    echo "Technical Workshop added successfully!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>