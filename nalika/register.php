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

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    try {
        if (!$conn || $conn->connect_error) {
            showModal('error', 'Database connection failed: ' . ($conn ? $conn->connect_error : 'No connection object'));
        }

        $firstName        = trim($_POST['firstName'] ?? '');
        $lastName         = trim($_POST['lastName'] ?? '');
        $rawUsername      = ucfirst($firstName) . ucfirst($lastName);
        $baseUsername     = !empty($rawUsername) ? substr($rawUsername, 0, 16) : 'User' . mt_rand(100, 999);
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $email            = trim($_POST['email'] ?? '');
        $contactRaw       = trim($_POST['phone'] ?? '');
        $age              = (int)($_POST['age'] ?? 0);
        $gender           = $_POST['gender'] ?? '';
        $role             = $_POST['type'] ?? '';
        $area             = $_POST['area'] ?? $_POST['darsArea'] ?? '';

        // Prevent INT overflow for phone column if schema uses int(20)
        $phoneDigits = preg_replace('/[^0-9]/', '', $contactRaw);
        $contact     = (int)min((float)$phoneDigits, 2147483647);

        // ✅ Password match check
        if ($password !== $confirm_password) {
            showModal('error', 'Passwords do not match. Please try again.');
        }

        if (empty($email) || empty($password) || empty($firstName)) {
            showModal('error', 'Please fill in all required fields.');
        }

        // ✅ Generate unique username (max 20 chars for varchar(20))
        $username = $baseUsername;
        $count    = 1;

        while (true) {
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            if (!$check) {
                showModal('error', 'Database query error (username check): ' . $conn->error);
            }
            $check->bind_param("s", $username);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows == 0) {
                $check->close();
                break;
            }

            $check->close();
            $suffix   = (string)$count;
            $maxBaseLen = 20 - strlen($suffix);
            $username = substr($baseUsername, 0, $maxBaseLen) . $suffix;
            $count++;
        }

        // ✅ Check if email already exists
        $check = $conn->prepare("SELECT email FROM users WHERE email = ?");
        if (!$check) {
            showModal('error', 'Database query error (email check): ' . $conn->error);
        }
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $check->close();
            showModal('error', 'This email is already registered. Try signing in instead.');
        }
        $check->close();

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
            if (function_exists('finfo_open')) {
                $finfo    = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $fileTmpPath);
                finfo_close($finfo);
            } else {
                $mimeType = $file['type'] ?? 'image/jpeg';
            }

            if (!in_array($mimeType, $allowedMimeTypes)) {
                showModal('error', 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.');
            }

            $mimeToExt = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];
            $ext = $mimeToExt[$mimeType] ?? 'jpg';

            $uploadDir = __DIR__ . '/uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Compact filename under 30 chars so full path fits varchar(50)
            $filename = 'u_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
            $destPath = $uploadDir . $filename;

            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                showModal('error', 'Failed to save profile image. Please try again.');
            }

            $imagePath = 'uploads/profiles/' . $filename;
        }

        // ✅ Only one representative allowed per area
        if ($role == "representative") {

            $checkRep = $conn->prepare("
                SELECT id FROM users
                WHERE area = ? AND role = 'representative'
                LIMIT 1
            ");
            if ($checkRep) {
                $checkRep->bind_param("s", $area);
                $checkRep->execute();
                $repResult = $checkRep->get_result();

                if ($repResult->num_rows > 0) {
                    $checkRep->close();
                    showModal('error', 'This area already has a representative.');
                }
                $checkRep->close();
            }
        }

        $activation_token = bin2hex(random_bytes(32));
        $hashed_password  = password_hash($password, PASSWORD_DEFAULT);

        // ✅ 12 columns matching 12 placeholders and "ssssssisssss"
        $stmt = $conn->prepare("
            INSERT INTO users
                (firstName, lastName, username, password, email, phone, age, gender, role, area, image, is_active, activation_token)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)
        ");

        if (!$stmt) {
            showModal('error', 'Database query error (user insert): ' . $conn->error);
        }

        $stmt->bind_param(
            "ssssssisssss",
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
            $imagePath,
            $activation_token
        );

        if ($stmt->execute()) {

            $domain = $_SERVER['HTTP_HOST'] ?? 'iqamateislam.site.je';
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $activationLink = "{$scheme}://{$domain}/nalika/activate.php?token=" . $activation_token;

            try {
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'chosama7777@gmail.com';
                    $mail->Password   = 'uutd jpxa uaue oibd'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->Timeout    = 4;

                    $mail->setFrom('chosama7777@gmail.com', 'Iqamat');
                    $mail->addAddress($email, $firstName);

                    $mail->isHTML(true);
                    $mail->Subject = 'Activate your account';
                    $mail->Body    = "Hi {$firstName},<br><br>
                        Please click the link below to activate your account:<br>
                        <a href=\"{$activationLink}\">{$activationLink}</a><br><br>
                        If you didn't sign up, you can ignore this email.";

                    $mail->send();
                    showModal('success', 'Registered! Check your email to activate your account.', '../index.php');
                } else {
                    showModal('success', 'Registered! Your account has been created.', '../index.php');
                }

            } catch (Throwable $e) {
                showModal('success', 'Account registered successfully! Note: Email delivery is pending. Please contact admin for activation.', '../index.php');
            }

        } else {
            showModal('error', 'Something went wrong while creating your account: ' . $stmt->error);
        }

        $stmt->close();
        $conn->close();

    } catch (Throwable $e) {
        showModal('error', 'Registration error: ' . $e->getMessage());
    }
}
?>