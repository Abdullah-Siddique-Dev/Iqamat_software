<?php
include "connection.php";

header('Content-Type: application/json');

$users = [];

$query = "SELECT * FROM users ORDER BY id DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(["error" => mysqli_error($conn)]);
    exit;
}

while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}

echo json_encode($users);
?>