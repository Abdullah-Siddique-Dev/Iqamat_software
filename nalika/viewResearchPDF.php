<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . "/connection.php";

if ($conn->connect_error) {
    http_response_code(500);
    echo "DB connection failed.";
    exit();
}

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit();
}

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo "Invalid ID.";
    exit();
}

$result = $conn->query("SELECT pdf_path, title FROM research WHERE id = $id");

if (!$result || $result->num_rows === 0) {
    http_response_code(404);
    echo "No PDF found for this research.";
    exit();
}

$row = $result->fetch_assoc();

if (empty($row['pdf_path'])) {
    http_response_code(404);
    echo "No PDF attached to this research.";
    exit();
}

$filePath = $row['pdf_path'];

// Relative path fallback for Linux live servers
if (!file_exists($filePath)) {
    $fileName = basename($filePath);
    $relPath  = __DIR__ . "/uploads/research/" . $fileName;
    if (file_exists($relPath)) {
        $filePath = $relPath;
    } else {
        http_response_code(404);
        echo "PDF file not found on server. Path: " . $filePath;
        exit();
    }
}

$safeTitle = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $row['title']);

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $safeTitle . '.pdf"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($filePath);
exit();
?>