<?php
header('Content-Type: application/json');
include "connection.php";

$result = $conn->query("
    SELECT id, CONCAT(firstName, ' ', lastName) AS name 
    FROM users 
    ORDER BY firstName ASC
");

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users);
$conn->close();
?>