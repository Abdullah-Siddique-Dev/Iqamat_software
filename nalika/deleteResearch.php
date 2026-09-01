<?php
include "connection.php";

session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit();
}

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    echo "Invalid ID.";
    exit();
}

// ── Fetch pdf_path before deleting ───────────────────────────
$id = intval($id);

$getQuery = mysqli_query(
    $conn,
    "SELECT pdf_path FROM research WHERE id = '$id'"
);

if (!$getQuery || mysqli_num_rows($getQuery) == 0) {
    echo "Record not found.";
    exit();
}

$row = mysqli_fetch_assoc($getQuery);

// ── Delete the physical PDF file ─────────────────────────────
if (!empty($row['pdf_path']) && file_exists($row['pdf_path'])) {
    unlink($row['pdf_path']);
}

// ── Delete DB record ─────────────────────────────────────────
$deleteQuery = mysqli_query(
    $conn,
    "DELETE FROM research WHERE id = '$id'"
);

if ($deleteQuery) {
    echo "Research deleted successfully!";
} else {
    echo "Database error: " . mysqli_error($conn);
}
?>