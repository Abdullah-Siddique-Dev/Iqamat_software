<?php
// submitTask.php - Member submits completed task with document upload
header('Content-Type: application/json');
ob_start();

include "connection.php";

// Get POST data
$task_id    = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
$user_id    = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$start_time = isset($_POST['start_time']) ? trim($_POST['start_time']) : null;
$end_time   = isset($_POST['end_time']) ? trim($_POST['end_time']) : null;

// Validation
if (!$task_id || !$user_id) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Missing task or user ID.']);
    exit;
}

// Handle file upload (same pattern as addResearch.php)
$documentPath = null;

if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['document'];
    $fileSize = $file['size'];
    $fileTmpPath = $file['tmp_name'];

    // Validate size (max 10MB)
    if ($fileSize > 10 * 1024 * 1024) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'File must be under 10 MB.']);
        exit;
    }

    // Validate MIME type
    $allowedMimeTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
        'image/jpg'
    ];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileTmpPath);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimeTypes)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, Word, and images allowed.']);
        exit;
    }

    // Determine extension
    $mimeToExt = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png'
    ];
    $ext = $mimeToExt[$mimeType] ?? 'bin';

    // Create upload directory
    $uploadDir = 'uploads/tasks/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $filename = uniqid('task_', true) . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (!move_uploaded_file($fileTmpPath, $destPath)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
        exit;
    }

    $documentPath = $destPath;
}

try {
    $stmt = $conn->prepare(
        "INSERT INTO task_submissions
            (task_id, user_id, start_time, end_time, document_path)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "iisss",
        $task_id, $user_id, $start_time, $end_time, $documentPath
    );
    $stmt->execute();
    $newId = $conn->insert_id;

    ob_clean();
    echo json_encode(['success' => true, 'id' => $newId]);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
