<?php
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include "connection.php";
include "message.php"; 

// ✅ Generate activation token
$activation_token = bin2hex(random_bytes(32));

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Auto-fix phone column if still INT (prevents 2147483647 truncation)
    $phoneColRes = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'phone'");
    if ($phoneColRes && $pRow = mysqli_fetch_assoc($phoneColRes)) {
        if (stripos($pRow['Type'], 'int') !== false) {
            mysqli_query($conn, "ALTER TABLE users MODIFY phone VARCHAR(30) NOT NULL");
        }
    }

    $firstName        = trim($_POST['firstName']);
    $lastName         = trim($_POST['lastName']);
    $baseUsername     = ucfirst($firstName) . ucfirst($lastName);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email            = trim($_POST['email']);
    $contact          = trim($_POST['phone']);
    $age              = $_POST['age'];
    $gender           = $_POST['gender'];
    $role             = $_POST['type'];
    $card             = $_POST['card'] ?? null;
    $category         = $_POST['category'] ?? null;
    $area             = $_POST['area'] ?? $_POST['darsArea'] ?? null;

    // ✅ Password match check
    if ($password !== $confirm_password) {
        showModal('error', 'Passwords do not match. Please try again.');
    }

    // ✅ Name & Phone validation
    if (preg_match('/[0-9]/', $firstName)) {
        showModal('error', 'First Name: Only letters allowed. No digits permitted.');
    }
    if (preg_match('/[0-9]/', $lastName)) {
        showModal('error', 'Last Name: Only letters allowed. No digits permitted.');
    }
    if (preg_match('/[a-zA-Z]/', $contact)) {
        showModal('error', 'Phone: Only numbers allowed. No letters permitted.');
    }

    // ✅ Generate unique username
    $username = $baseUsername;
    $count    = 1;

    while (true) {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows == 0) {
            break;
        }

        $username = $baseUsername . $count;
        $count++;
    }

    // ✅ Check if email already exists
    $check = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        showModal('error', 'This email is already registered. Try signing in instead.');
    }

    // ✅ Handle profile image upload
    $imagePath = NULL;

    if (!empty($_FILES['profile_image']['name'])) {

        $file        = $_FILES['profile_image'];
        $fileSize    = $file['size'];
        $fileTmpPath = $file['tmp_name'];
        $fileError   = $file['error'];

        if ($fileError !== UPLOAD_ERR_OK) {
            showModal('error', 'File upload error. Please try again.');
        }

        if ($fileSize > 5 * 1024 * 1024) {
            showModal('error', 'Profile image must be under 5 MB.');
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileTmpPath);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            showModal('error', 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.');
        }

        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];
        $ext = $mimeToExt[$mimeType];

        $uploadDir = 'uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid('user_', true) . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($fileTmpPath, $destPath)) {
            showModal('error', 'Failed to save profile image. Please try again.');
        }

        $imagePath = $destPath;
    }

    // ✅ Only one representative allowed per area
    if ($role == "representative") {

        $checkRep = $conn->prepare("
            SELECT id FROM users
            WHERE area = ? AND role = 'representative'
            LIMIT 1
        ");
        $checkRep->bind_param("s", $area);
        $checkRep->execute();
        $repResult = $checkRep->get_result();

        if ($repResult->num_rows > 0) {
            showModal('error', 'This area already has a representative.');
        }

        $checkRep->close();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        INSERT INTO users
            (firstName, lastName, username, password, email, phone, age, gender, role, area, card, category, image, is_active, approval_status, activation_token)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'pending', ?)
    ");

    $stmt->bind_param(
        "ssssssisssssss",
        $firstName,
        $lastName,
        $username,
        $hashed_password,
        $email,
        $contact,
        $age,
        $gender,
        $role,
        $area,
        $card,
        $category,
        $imagePath,
        $activation_token
    );

    if ($stmt->execute()) {

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'iqamat.free.je';
        $activationLink = $protocol . $host . "/nalika/activate.php?token=" . $activation_token;

        $emailSent = false;
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'chosama7777@gmail.com';
                $mail->Password   = 'uutd jpxa uaue oibd'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('chosama7777@gmail.com', 'Iqamat');
                $mail->addAddress($email, $firstName);

                $mail->isHTML(true);
                $mail->Subject = 'Activate your account';
                $mail->Body    = "Hi {$firstName},<br><br>
                    Please click the link below to activate your account:<br>
                    <a href=\"{$activationLink}\">{$activationLink}</a><br><br>
                    If you didn't sign up, you can ignore this email.";

                $mail->send();
                $emailSent = true;
            } catch (\Throwable $e) {
                $emailSent = false;
            }
        }

        if ($emailSent) {
            showModal('success', 'Registered! Check your email to activate your account.', '../index.php');
        } else {
            showModal('success', 'Registration submitted! Please wait for admin approval.', '../index.php');
        }

    } else {
        showModal('error', 'Something went wrong while creating your account. Please try again.');
    }

    $stmt->close();
    $conn->close();
}
?>