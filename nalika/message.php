<?php
/**
 * Renders a styled modal message and optionally redirects.
 * $type: 'success' | 'error'
 * $message: text to display
 * $redirect: URL to redirect to after modal closes (optional)
 */
function showModal($type, $message, $redirect = null) {
    $isSuccess = $type === 'success';
    $icon      = $isSuccess ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
    $iconColor = $isSuccess ? '#03a9f4' : '#f44336';
    $title     = $isSuccess ? 'Success' : 'Something went wrong';
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?= $title ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
        <style>
            body {
                background: #152036;
                font-family: 'Roboto', sans-serif;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .nk-modal-card {
                background: #1b2a47;
                border: 1px solid rgba(255,255,255,0.06);
                border-radius: 12px;
                width: 100%;
                max-width: 400px;
                padding: 40px 32px;
                text-align: center;
            }
            .nk-modal-icon { font-size: 48px; color: <?= $iconColor ?>; margin-bottom: 16px; }
            .nk-modal-title { color: #fff; font-size: 20px; font-weight: 600; margin-bottom: 10px; }
            .nk-modal-text { color: #8a9bb5; font-size: 14px; margin-bottom: 24px; }
            .btn-nk-primary {
                background: #03a9f4;
                border: none;
                color: #fff;
                border-radius: 8px;
                padding: 10px 24px;
                font-weight: 500;
                font-size: 14px;
            }
            .btn-nk-primary:hover { background: #0290d1; color: #fff; }
        </style>
    </head>
    <body>
        <div class="nk-modal-card">
            <div class="nk-modal-icon"><i class="bi <?= $icon ?>"></i></div>
            <div class="nk-modal-title"><?= htmlspecialchars($title) ?></div>
            <div class="nk-modal-text"><?= htmlspecialchars($message) ?></div>
            <?php if ($redirect): ?>
                <a href="<?= htmlspecialchars($redirect) ?>" class="btn btn-nk-primary">Continue</a>
            <?php else: ?>
                <a href="javascript:history.back()" class="btn btn-nk-primary">Go Back</a>
            <?php endif; ?>
        </div>
        <?php if ($redirect): ?>
        <script>
            setTimeout(() => { window.location.href = "<?= htmlspecialchars($redirect) ?>"; }, 3000);
        </script>
        <?php endif; ?>
    </body>
    </html>
    <?php
    exit();
}