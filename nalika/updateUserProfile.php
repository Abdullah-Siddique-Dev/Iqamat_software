<?php
include "connection.php";
session_start();

if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    echo "Unauthorized"; exit();
}

$loggedId   = (int)$_SESSION['user']['id'];
$loggedRole = $_SESSION['user']['role'];

$targetId = isset($_POST['id']) ? (int)$_POST['id'] : $loggedId;

// Only allow editing own profile or DG editing anyone
if ($targetId !== $loggedId && $loggedRole !== 'DG') {
    echo "Permission denied."; exit();
}

$firstName = trim($_POST['firstName'] ?? '');
$lastName  = trim($_POST['lastName']  ?? '');
$email     = trim($_POST['email']     ?? '');
$phone     = trim($_POST['phone']     ?? '');
$age       = (int)($_POST['age']      ?? 0);
$gender    = trim($_POST['gender']    ?? '');
$cnic      = trim($_POST['cnic']      ?? '');

if (empty($firstName) || empty($lastName)) {
    echo "First and Last name are required."; exit();
}

if (preg_match('/[0-9]/', $firstName) || preg_match('/[0-9]/', $lastName)) {
    echo "Error: Only letters allowed for Name. No digits permitted."; exit();
}

if (!empty($phone) && preg_match('/[a-zA-Z]/', $phone)) {
    echo "Error: Only numbers allowed for Phone. No letters permitted."; exit();
}

$stmt = $conn->prepare("
    UPDATE users
    SET firstName=?, lastName=?, email=?, phone=?, age=?, gender=?, cnic=?
    WHERE id=?
");
$stmt->bind_param("ssssissi", $firstName, $lastName, $email, $phone, $age, $gender, $cnic, $targetId);

if ($stmt->execute()) {
    // Refresh session if own profile
    if ($targetId === $loggedId) {
        $_SESSION['user']['firstName'] = $firstName;
        $_SESSION['user']['lastName']  = $lastName;
        $_SESSION['user']['email']     = $email;
    }
    echo "Profile updated successfully!";
} else {
    echo "Failed to update profile: " . $conn->error;
}
$stmt->close();