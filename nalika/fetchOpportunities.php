<?php
include "connection.php";
session_start();
if (!isset($_SESSION['user'])) { http_response_code(401); exit(); }

$sql  = "SELECT * FROM jobs_internships ORDER BY created_at DESC";
$res  = mysqli_query($conn, $sql);
$data = [];
while ($row = mysqli_fetch_assoc($res)) {
    $data[] = $row;
}
header('Content-Type: application/json');
echo json_encode($data);