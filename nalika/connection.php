<?php
// Disable strict exception throwing in PHP 8.1+ for mysqli to prevent HTTP 500 crashes
mysqli_report(MYSQLI_REPORT_OFF);

$isLocal = (
    (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'localhost') ||
    (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] === '127.0.0.1') ||
    php_sapi_name() === 'cli'
);

try {
    if ($isLocal) {
        // XAMPP Local Database
        $conn = @new mysqli("localhost", "root", "", "iqamat");
    } else {
        // InfinityFree Live Production Database
        $host = "sql202.infinityfree.com";
        $user = "if0_42722491";
        $pass = "1234Nayyab";
        $db   = "if0_42722491_iqamat";

        $conn = @new mysqli($host, $user, $pass, $db);
    }
} catch (Throwable $e) {
    $conn = null;
    $dbError = $e->getMessage();
}

if (!$conn || $conn->connect_error) {
    $errorMsg = isset($dbError) ? $dbError : ($conn ? $conn->connect_error : "Unable to connect to MySQL database.");
    die("<div style='background:#1b2a47;color:#fff;font-family:sans-serif;padding:30px;text-align:center;border-radius:10px;max-width:500px;margin:50px auto;border:1px solid #253a5c;'>
        <h3 style='color:#f44336;'>Database Connection Failed</h3>
        <p style='color:#8a9bb5;font-size:14px;'>" . htmlspecialchars($errorMsg) . "</p>
        <p style='color:#5a6a7f;font-size:12px;'>Please verify database host, username, and password in <code>connection.php</code>.</p>
    </div>");
}

$conn->set_charset("utf8mb4");
?>