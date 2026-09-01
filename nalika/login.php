<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/connection.php";
require_once __DIR__ . "/message.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    try {
        if (!$conn || $conn->connect_error) {
            showModal('error', 'Database connection failed: ' . ($conn ? $conn->connect_error : 'No connection object'));
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            showModal('error', 'Please enter both email and password.');
        }

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        if (!$stmt) {
            showModal('error', 'Database query error: ' . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                // ✅ Block unverified emails
                if (isset($user['is_active']) && $user['is_active'] == 0) {
                    showModal('error', "Your account isn't activated yet. Please check your email and click the activation link we sent you.");
                    $stmt->close();
                    $conn->close();
                    exit();
                }

                // ✅ Block accounts pending admin approval
                if (isset($user['approval_status']) && $user['approval_status'] === 'pending') {
                    showModal('error', "Your email is verified, but your account is still awaiting admin approval. You'll be notified once it's approved.");
                    $stmt->close();
                    $conn->close();
                    exit();
                }

                // ✅ Block rejected accounts
                if (isset($user['approval_status']) && $user['approval_status'] === 'rejected') {
                    showModal('error', "Your registration was not approved. Please contact support for more information.");
                    $stmt->close();
                    $conn->close();
                    exit();
                }

                // ✅ All checks passed — log in
                unset($user['password']);
                $_SESSION['user'] = $user;

                $stmt->close();
                $conn->close();

                header("Location: dashboard.php");
                exit();

            } else {
                showModal('error', 'Incorrect password. Please try again.');
            }

        } else {
            showModal('error', 'No account found with that email.');
        }

        $stmt->close();
        $conn->close();

    } catch (Throwable $e) {
        showModal('error', 'Login error: ' . $e->getMessage());
    }
} else {
    // If accessed via GET directly, redirect to login index page
    header("Location: ../index.php");
    exit();
}
?>