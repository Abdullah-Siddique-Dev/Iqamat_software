<?php
// Disable output buffering to prevent header pollution
while (ob_get_level()) {
    ob_end_clean();
}

include "connection.php";
include "auth.php";

$rawFileParam = $_GET['file'] ?? '';
$filePath = $rawFileParam;
$decoded = json_decode($rawFileParam, true);
if (is_array($decoded) && !empty($decoded)) {
    $filePath = $decoded[0];
} elseif (strpos($rawFileParam, ',') !== false) {
    $parts = explode(',', $rawFileParam);
    $filePath = trim($parts[0]);
}

if (empty($filePath)) {
    http_response_code(400);
    echo "Invalid file parameter.";
    exit();
}

function resolveFilePath($filePath) {
    $cleanPath = ltrim(str_replace('\\', '/', $filePath), '/');
    $candidates = [
        __DIR__ . '/' . $cleanPath,
        dirname(__DIR__) . '/' . $cleanPath,
        __DIR__ . '/uploads/documents/' . basename($cleanPath),
        dirname(__DIR__) . '/uploads/documents/' . basename($cleanPath),
    ];
    foreach ($candidates as $candidate) {
        $real = @realpath($candidate);
        if ($real && file_exists($real)) {
            return $real;
        }
    }
    return false;
}

$fullPath = resolveFilePath($filePath);

if (!$fullPath) {
    http_response_code(404);
    echo "File not found on server.";
    exit();
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

$mimeTypes = [
    'pdf'  => 'application/pdf',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'webp' => 'image/webp',
    'gif'  => 'image/gif',
    'txt'  => 'text/plain',
    'html' => 'text/html',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: private, max-age=86400');
header('Pragma: public');

readfile($fullPath);
exit();
?>
