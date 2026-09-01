<?php
include "connection.php";

$status  = 'error';
$message = 'No activation token provided.';

if (isset($_GET['token']) && !empty($_GET['token'])) {

    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE activation_token = ? AND is_active = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        $update = $conn->prepare("UPDATE users SET is_active = 1, activation_token = NULL WHERE id = ?");
        $update->bind_param("i", $user['id']);
        $update->execute();
        $update->close();

        $status  = 'pending';
        $message = 'Your email has been verified! Your account is now waiting for admin approval. You will be able to sign in once an admin approves your registration.';
    } else {
        $status  = 'error';
        $message = 'Invalid or already-used activation link.';
    }

    $stmt->close();
}

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Account Activation | Iqamat</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" type="image/x-icon" href="../graphics/Logo/Iqamat Logo Transparent.png" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
    <style>
        body {
            background: #152036;
            font-family: 'Roboto', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .activation-card {
            background: #1b2a47;
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            width: 100%;
            max-width: 440px;
            padding: 48px 40px 40px;
            margin: 16px;
            text-align: center;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(24px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .activation-icon-wrap {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 2rem;
        }

        .activation-icon-wrap.success {
            background: rgba(0, 227, 150, 0.12);
            border: 1.5px solid rgba(0, 227, 150, 0.25);
            color: #00e396;
        }

        .activation-icon-wrap.pending {
            background: rgba(255, 193, 7, 0.12);
            border: 1.5px solid rgba(255, 193, 7, 0.3);
            color: #ffc107;
        }

        .activation-icon-wrap.error {
            background: rgba(255, 69, 96, 0.12);
            border: 1.5px solid rgba(255, 69, 96, 0.25);
            color: #ff4560;
        }

        .activation-title {
            color: #fff;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .activation-message {
            color: rgba(255,255,255,0.5);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .btn-activation {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #03a9f4;
            border: none;
            color: #fff;
            border-radius: 8px;
            padding: 12px 28px;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.2s;
            font-family: 'Roboto', sans-serif;
        }

        .btn-activation:hover {
            background: #0290d1;
            color: #fff;
        }

        .btn-activation-outline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            border: 1px solid #253a5c;
            color: #8a9bb5;
            border-radius: 8px;
            padding: 12px 28px;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            font-family: 'Roboto', sans-serif;
        }

        .btn-activation-outline:hover {
            border-color: #03a9f4;
            color: #03a9f4;
        }

        .nk-divider {
            height: 1px;
            background: rgba(255,255,255,0.06);
            margin: 28px 0;
        }

        .activation-footer {
            color: #3a4a5c;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="activation-card">

        <!-- Icon -->
        <div class="activation-icon-wrap <?= $status ?>">
            <?php if ($status === 'success'): ?>
                <i class="bi bi-check-lg"></i>
            <?php elseif ($status === 'pending'): ?>
                <i class="bi bi-hourglass-split"></i>
            <?php else: ?>
                <i class="bi bi-x-lg"></i>
            <?php endif; ?>
        </div>

        <!-- Title -->
        <div class="activation-title">
            <?php if ($status === 'success'): ?>
                Account Activated!
            <?php elseif ($status === 'pending'): ?>
                Email Verified!
            <?php else: ?>
                Activation Failed
            <?php endif; ?>
        </div>

        <!-- Message -->
        <div class="activation-message">
            <?= htmlspecialchars($message) ?>
        </div>

        <!-- Actions -->
        <?php if ($status === 'pending'): ?>
            <a href="../index.php" class="btn-activation">
                <i class="bi bi-house"></i>
                Go to Home
            </a>
        <?php elseif ($status === 'success'): ?>
            <a href="../index.php" class="btn-activation">
                <i class="bi bi-box-arrow-in-right"></i>
                Sign In Now
            </a>
        <?php else: ?>
            <div class="d-flex flex-column gap-3 align-items-center">
                <a href="../index.php" class="btn-activation">
                    <i class="bi bi-house"></i>
                    Go to Home
                </a>
                <a href="signup.php" class="btn-activation-outline">
                    <i class="bi bi-person-plus"></i>
                    Register Again
                </a>
            </div>
        <?php endif; ?>

        <div class="nk-divider"></div>

        <div class="activation-footer">
            &copy; <?= date('Y') ?> Iqamat E Islam &nbsp;·&nbsp; All rights reserved
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>