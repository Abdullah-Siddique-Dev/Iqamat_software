<?php

include "connection.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Invalid or broken session → force login
if (
    !isset($_SESSION['user']) ||
    !is_array($_SESSION['user']) ||
    empty($_SESSION['user']['id'])
) {
    session_unset();
    session_destroy();

    header("Location: ../index.php");
    exit();
}

$userId = (int) $_SESSION['user']['id'];

// Always fetch latest user data from database
$stmt = $conn->prepare("
    SELECT 
        id,
        username,
        role,
        area,
        card,
        category,
        firstName,
        lastName,
        email,
        image
    FROM users
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Database query preparation failed.");
}

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

// User doesn't exist anymore → logout
if ($result->num_rows === 0) {
    $stmt->close();

    session_unset();
    session_destroy();

   header("Location: ../index.php");
    exit();
}

$freshUser = $result->fetch_assoc();

$stmt->close();

// Update session with latest user data
$_SESSION['user'] = $freshUser;

// Convenience variables
$loggedUserId    = (int) ($freshUser['id'] ?? 0);
$loggedUsername  = $freshUser['username'] ?? '';
$loggedRole      = $freshUser['role'] ?? '';
$loggedArea      = (int) ($freshUser['area'] ?? 0);
$loggedCard      = !empty($freshUser['card']) ? $freshUser['card'] : 'Diamond';
$loggedCategory  = !empty($freshUser['category']) ? $freshUser['category'] : 'B';
$loggedFirstName = $freshUser['firstName'] ?? '';
$loggedLastName  = $freshUser['lastName'] ?? '';
$loggedEmail     = $freshUser['email'] ?? '';
$loggedImage     = $freshUser['image'] ?? '';