<?php
include "connection.php";
session_start();

if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    echo "Unauthorized"; exit();
}

$loggedId   = (int)$_SESSION['user']['id'];
$loggedRole = $_SESSION['user']['role'];

$targetId = isset($_POST['id']) ? (int)$_POST['id'] : $loggedId;

// Only allow editing own profile or Admin/DG/MD editing anyone
$canEditAny = in_array(strtolower($loggedRole), ['dg','md','admin','administrator','adminsir']) || stripos($loggedRole, 'admin') !== false;
if ($targetId !== $loggedId && !$canEditAny) {
    echo "Permission denied."; exit();
}

$firstName = trim($_POST['firstName'] ?? '');
$lastName  = trim($_POST['lastName']  ?? '');
$email     = trim($_POST['email']     ?? '');
$phone     = trim($_POST['phone']     ?? '');
$age       = (int)($_POST['age']      ?? 0);
$gender    = trim($_POST['gender']    ?? '');
$cnic      = trim($_POST['cnic']      ?? '');
$card      = trim($_POST['card']      ?? '');
$category  = trim($_POST['category']  ?? '');

if (empty($firstName) || empty($lastName)) {
    echo "First and Last name are required."; exit();
}

if (preg_match('/[0-9]/', $firstName) || preg_match('/[0-9]/', $lastName)) {
    echo "Error: Only letters allowed for Name. No digits permitted."; exit();
}

if (!empty($phone) && preg_match('/[a-zA-Z]/', $phone)) {
    echo "Error: Only numbers allowed for Phone. No letters permitted."; exit();
}

// Ensure card & category columns exist
$colCard = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'card'");
if (!$colCard || mysqli_num_rows($colCard) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN card VARCHAR(50) DEFAULT 'Diamond' AFTER area");
}
$colCat = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'category'");
if (!$colCat || mysqli_num_rows($colCat) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN category VARCHAR(50) DEFAULT 'B' AFTER card");
}

$stmt = $conn->prepare("
    UPDATE users
    SET firstName=?, lastName=?, email=?, phone=?, age=?, gender=?, cnic=?, card=?, category=?
    WHERE id=?
");
$stmt->bind_param("ssssissssi", $firstName, $lastName, $email, $phone, $age, $gender, $cnic, $card, $category, $targetId);

if ($stmt->execute()) {
    // Refresh session if own profile
    if ($targetId === $loggedId) {
        $_SESSION['user']['firstName'] = $firstName;
        $_SESSION['user']['lastName']  = $lastName;
        $_SESSION['user']['email']     = $email;
        if (!empty($card)) $_SESSION['user']['card'] = $card;
        if (!empty($category)) $_SESSION['user']['category'] = $category;
    }
    echo "Profile updated successfully!";
} else {
    echo "Failed to update profile: " . $conn->error;
}
$stmt->close();