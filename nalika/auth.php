<?php
include "connection.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ❌ Invalid or broken session → force login
if (
    !isset($_SESSION['user']) ||
    !is_array($_SESSION['user']) ||
    empty($_SESSION['user']['id'])
) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

$userId = (int)$_SESSION['user']['id'];

// 🔥 ALWAYS FETCH LATEST USER DATA FROM DB
$stmt = $conn->prepare("
    SELECT id, username, role, area, firstName, lastName, email, image
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// ❌ If user deleted or invalid → logout
if ($result->num_rows === 0) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

$freshUser = $result->fetch_assoc();
$stmt->close();

// 🔥 UPDATE SESSION WITH LATEST DATA
$_SESSION['user'] = $freshUser;

// ── Convenience variables ──
$loggedUserId    = (int)($freshUser['id'] ?? 0);
$loggedUsername  =       $freshUser['username'] ?? '';
$loggedRole      =       $freshUser['role'] ?? '';
$loggedArea      = (int)($freshUser['area'] ?? 0);
$loggedFirstName =       $freshUser['firstName'] ?? '';
$loggedLastName  =       $freshUser['lastName'] ?? '';
$loggedEmail     =       $freshUser['email'] ?? '';
$loggedImage = $freshUser['image'] ?? '';