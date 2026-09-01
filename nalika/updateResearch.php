<?php
include "connection.php";

session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit();
}

$id            = intval($_POST['id'] ?? 0);
$title         = trim($_POST['title'] ?? '');
$author_id     = intval($_POST['author_id'] ?? 0);
$research_type = trim($_POST['research_type'] ?? '');

if (!$id || !$title || !$author_id || !$research_type) {
    echo "All fields are required.";
    exit();
}

// ── Fetch existing record ────────────────────────────────────
$id = intval($id);

$existing = mysqli_query($conn, "SELECT pdf_path FROM research WHERE id = '$id'");

if (!$existing || mysqli_num_rows($existing) == 0) {
    echo "Research not found.";
    exit();
}

$row = mysqli_fetch_assoc($existing);

$pdf_path = $row['pdf_path']; // keep existing by default

// ── Handle optional PDF replacement ─────────────────────────
if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {

    $uploadDir = 'uploads/research/';

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

    if ($fileSize > 20 * 1024 * 1024) {
        echo "PDF file size must not exceed 20 MB.";
        exit();
    }

    $safeName = preg_replace(
        '/[^a-zA-Z0-9_\-]/',
        '_',
        pathinfo($_FILES['pdf_file']['name'], PATHINFO_FILENAME)
    );

    $fileName = time() . '_' . $safeName . '.pdf';
    $destPath = $uploadDir . $fileName;

    if (!move_uploaded_file($fileTmp, $destPath)) {
        echo "Failed to upload PDF.";
        exit();
    }

    // Delete old file
    if ($pdf_path && file_exists($pdf_path)) {
        unlink($pdf_path);
    }

    $pdf_path = $destPath;
}

// ── Escape values ────────────────────────────────────────────
$title         = mysqli_real_escape_string($conn, $title);
$research_type = mysqli_real_escape_string($conn, $research_type);
$pdf_path      = mysqli_real_escape_string($conn, $pdf_path);

// ── Update DB ────────────────────────────────────────────────
$sql = "
    UPDATE research
    SET title = '$title',
        author_id = '$author_id',
        research_type = '$research_type',
        pdf_path = '$pdf_path',
        updated_at = NOW()
    WHERE id = '$id'
";

if (mysqli_query($conn, $sql)) {
    echo "Research updated successfully!";
} else {
    echo "Database error: " . mysqli_error($conn);
}
?>