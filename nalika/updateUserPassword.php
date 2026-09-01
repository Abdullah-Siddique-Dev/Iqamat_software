<?php
include "connection.php";
session_start();

if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    echo "Unauthorized"; exit();
}

$loggedId   = (int)$_SESSION['user']['id'];
$loggedRole = $_SESSION['user']['role'];

$targetId    = isset($_POST['id']) ? (int)$_POST['id'] : $loggedId;
$newPassword = trim($_POST['newPassword'] ?? '');

// Only allow changing own password or DG changing anyone's
if ($targetId !== $loggedId && $loggedRole !== 'DG') {
    echo "Permission denied."; exit();
}

if (strlen($newPassword) < 6) {
    echo "Password must be at least 6 characters."; exit();
}

$hashed = password_hash($newPassword, PASSWORD_BCRYPT);
$stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
$stmt->bind_param("si", $hashed, $targetId);

if ($stmt->execute()) {
    echo "Password changed successfully!";
} else {
    echo "Failed to change password: " . $conn->error;
}
$stmt->close();