<?php
include "connection.php";
session_start();
if (!isset($_SESSION['user'])) { exit("Unauthorized"); }

$id            = intval($_POST['id']            ?? 0);
$topic         = trim($_POST['topic']           ?? '');
$skills        = trim($_POST['skills']          ?? '');
$organisier    = trim($_POST['organisier']      ?? '');
$dateTime      = trim($_POST['dateTime']        ?? '') ?: null;
$durationValue = intval($_POST['durationValue'] ?? 0) ?: null;
$durationUnit  = trim($_POST['durationUnit']    ?? 'Months');
$frequency     = intval($_POST['frequency']     ?? 0) ?: null;
$phone         = trim($_POST['phone']           ?? '');
$location      = trim($_POST['location']        ?? '');
$feeType       = trim($_POST['feeType']         ?? 'Free');
$feeAmount     = ($feeType === 'Paid') ? trim($_POST['feeAmount'] ?? '') : null;

if (!$id || !$topic || !$skills || !$organisier) {
    echo "Topic, Skills and Organiser are required.";
    exit;
}

$stmt = $conn->prepare("
    UPDATE technical_workshops SET
        topic         = ?,
        skills        = ?,
        organisier    = ?,
        dateTime      = ?,
        durationValue = ?,
        durationUnit  = ?,
        frequency     = ?,
        phone         = ?,
        location      = ?,
        feeType       = ?,
        feeAmount     = ?
    WHERE id = ?
");

// s s s s i s i s s s s i  = 12 params
$stmt->bind_param("ssssissssssi",
    $topic, $skills, $organisier, $dateTime,
    $durationValue, $durationUnit, $frequency,
    $phone, $location, $feeType, $feeAmount,
    $id
);

if ($stmt->execute()) {
    echo "Technical Workshop updated successfully!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>