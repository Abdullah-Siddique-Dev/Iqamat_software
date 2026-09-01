<?php
include "connection.php";
session_start();
if (!isset($_SESSION['user'])) { http_response_code(401); exit(); }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo "Invalid ID."; exit(); }

$sql = "DELETE FROM jobs_internships WHERE id = $id";
if (mysqli_query($conn, $sql)) {
    echo "Deleted successfully!";
} else {
    echo "Error: " . mysqli_error($conn);
}