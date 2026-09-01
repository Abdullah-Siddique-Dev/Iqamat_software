<?php
include "connection.php";
session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit();
}

$title         = trim($_POST['title'] ?? '');
$author_id     = intval($_POST['author_id'] ?? 0);
$research_type = trim($_POST['research_type'] ?? '');

if (!$title || !$author_id || !$research_type) {
    echo "All fields are required.";
    exit();
}

$pdf_path = null;

// ── Handle PDF upload ────────────────────────────────────────
if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {

    $uploadDir = 'uploads/research/';

    // Create directory if it does not exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileTmp  = $_FILES['pdf_file']['tmp_name'];
    $fileType = mime_content_type($fileTmp);

    if ($fileType !== 'application/pdf') {
        echo "Only PDF files are allowed.";
        exit();
    }

    $fileSize = $_FILES['pdf_file']['size'];

    if ($fileSize > 20 * 1024 * 1024) { // 20 MB limit
        echo "PDF file size must not exceed 20 MB.";
        exit();
    }

    // Generate unique filename
    $safeName = preg_replace(
        '/[^a-zA-Z0-9_\-]/',
        '_',
        pathinfo($_FILES['pdf_file']['name'], PATHINFO_FILENAME)
    );

    $fileName = time() . '_' . $safeName . '.pdf';
    $destPath = $uploadDir . $fileName;

    if (!move_uploaded_file($fileTmp, $destPath)) {
        echo "Failed to upload PDF. Please try again.";
        exit();
    }

    $pdf_path = $destPath;

} else {
    echo "A PDF file is required.";
    exit();
}

// ── Insert into DB using mysqli conn ─────────────────────────
$title         = mysqli_real_escape_string($conn, $title);
$research_type = mysqli_real_escape_string($conn, $research_type);
$pdf_path      = mysqli_real_escape_string($conn, $pdf_path);

$sql = "
    INSERT INTO research
    (title, author_id, research_type, pdf_path, created_at)
    VALUES
    ('$title', '$author_id', '$research_type', '$pdf_path', NOW())
";

if (mysqli_query($conn, $sql)) {
    echo "Research added successfully!";
} else {

    // Delete uploaded file if DB insert fails
    if ($pdf_path && file_exists($pdf_path)) {
        unlink($pdf_path);
    }

    echo "Database error: " . mysqli_error($conn);
}
?>