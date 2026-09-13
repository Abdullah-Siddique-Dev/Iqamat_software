<?php
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
$fileName = !empty($filePath) ? basename($filePath) : 'Document';
$ext = !empty($fullPath) ? strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) : '';
$rawUrl = 'rawFile.php?file=' . urlencode($filePath);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Viewer - <?php echo htmlspecialchars($fileName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #0b111e;
            color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }
        .viewer-header {
            background: #192436;
            border-bottom: 1px solid #293647;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 10;
        }
        .viewer-title {
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .viewer-body {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: #0b111e;
            overflow: auto;
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        img.preview-img {
            max-width: 95%;
            max-height: 90vh;
            object-fit: contain;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border-radius: 8px;
        }
        .fallback-card {
            background: #192436;
            border: 1px solid #293647;
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            max-width: 500px;
        }
    </style>
</head>
<body>
    <div class="viewer-header">
        <div class="viewer-title">
            <i class="bi bi-file-earmark-text text-info fs-4"></i>
            <span><?php echo htmlspecialchars($fileName); ?></span>
            <span class="badge bg-secondary ms-2" style="text-transform:uppercase; font-size:0.75rem;"><?php echo htmlspecialchars($ext ?: 'FILE'); ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ($fullPath): ?>
                <a href="<?php echo $rawUrl; ?>" download="<?php echo htmlspecialchars($fileName); ?>" class="btn btn-sm btn-outline-info">
                    <i class="bi bi-download me-1"></i> Download File
                </a>
            <?php endif; ?>
            <button onclick="window.close();" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x-lg me-1"></i> Close Tab
            </button>
        </div>
    </div>

    <div class="viewer-body">
        <?php if (!$fullPath): ?>
            <div class="fallback-card">
                <i class="bi bi-exclamation-triangle-fill text-warning display-3 mb-3"></i>
                <h5>Document Not Found</h5>
                <p class="text-muted small">The requested document file could not be found on the server.</p>
            </div>
        <?php elseif (in_array($ext, ['pdf', 'txt', 'html'])): ?>
            <iframe src="<?php echo $rawUrl; ?>"></iframe>
        <?php elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])): ?>
            <img src="<?php echo $rawUrl; ?>" class="preview-img" alt="<?php echo htmlspecialchars($fileName); ?>">
        <?php else: ?>
            <div class="fallback-card">
                <i class="bi bi-file-earmark-word text-info display-3 mb-3"></i>
                <h5 class="text-white mb-2"><?php echo htmlspecialchars($fileName); ?></h5>
                <p class="text-muted small mb-4">This document format (<?php echo strtoupper($ext); ?>) cannot be rendered directly inside the browser tab.</p>
                <a href="<?php echo $rawUrl; ?>" download="<?php echo htmlspecialchars($fileName); ?>" class="btn btn-info text-white font-weight-bold px-4 py-2">
                    <i class="bi bi-download me-2"></i>Download Document
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
