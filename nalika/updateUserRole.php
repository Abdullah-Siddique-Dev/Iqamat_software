<?php
include "connection.php";
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit();
}

$id   = intval($_POST['id']   ?? 0);
$role = trim($_POST['role']   ?? '');

$allowedRoles = [
    'member', 'trainee', 'committee',
    'itHead', 'researchHead', 'representative',
    'MD', 'DG'
];

if (!$id || !$role) {
    echo "Missing required fields.";
    exit();
}

if (!in_array($role, $allowedRoles)) {
    echo "Invalid role.";
    exit();
}

$stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
$stmt->bind_param("si", $role, $id);

if ($stmt->execute()) {
    $roleLabels = [
        'member'         => 'Member',
        'trainee'        => 'Trainee',
        'committee'      => 'Committee Member',
        'itHead'         => 'IT Head',
        'researchHead'   => 'Research Head',
        'representative' => 'Representative',
        'MD'             => 'Managing Director',
        'DG'             => 'Director General',
    ];
    $label = $roleLabels[$role] ?? $role;
    echo "Role updated to \"$label\" successfully!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>