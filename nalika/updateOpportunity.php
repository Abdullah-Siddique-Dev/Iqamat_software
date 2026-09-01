<?php
include "connection.php";
session_start();
if (!isset($_SESSION['user'])) { http_response_code(401); exit(); }

$id                  = (int)($_POST['id'] ?? 0);
$title               = mysqli_real_escape_string($conn, trim($_POST['title']               ?? ''));
$type                = mysqli_real_escape_string($conn, trim($_POST['type']                ?? 'Job'));
$company_name        = mysqli_real_escape_string($conn, trim($_POST['company_name']        ?? ''));
$location_type       = mysqli_real_escape_string($conn, trim($_POST['location_type']       ?? 'Physical'));
$location_address    = mysqli_real_escape_string($conn, trim($_POST['location_address']    ?? ''));
$salary              = mysqli_real_escape_string($conn, trim($_POST['salary']              ?? ''));
$timings             = mysqli_real_escape_string($conn, trim($_POST['timings']             ?? ''));
$company_contact     = mysqli_real_escape_string($conn, trim($_POST['company_contact']     ?? ''));
$referred_by_name    = mysqli_real_escape_string($conn, trim($_POST['referred_by_name']    ?? ''));
$referred_by_contact = mysqli_real_escape_string($conn, trim($_POST['referred_by_contact'] ?? ''));
$posted_date         = mysqli_real_escape_string($conn, trim($_POST['posted_date']         ?? ''));
$status              = mysqli_real_escape_string($conn, trim($_POST['status']              ?? 'Active'));

if (!$id || !$title || !$type) {
    echo "Invalid data."; exit();
}

$posted_date_val = $posted_date ? "'$posted_date'" : "NULL";

$sql = "UPDATE jobs_internships SET
            title               = '$title',
            type                = '$type',
            company_name        = '$company_name',
            location_type       = '$location_type',
            location_address    = '$location_address',
            salary              = '$salary',
            timings             = '$timings',
            company_contact     = '$company_contact',
            referred_by_name    = '$referred_by_name',
            referred_by_contact = '$referred_by_contact',
            posted_date         = $posted_date_val,
            status              = '$status'
        WHERE id = $id";

if (mysqli_query($conn, $sql)) {
    echo "Updated successfully!";
} else {
    echo "Error: " . mysqli_error($conn);
}