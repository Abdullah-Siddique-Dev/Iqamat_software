<?php
include "connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Auto-fix phone column if still INT (prevents 2147483647 truncation)
    $phoneColRes = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'phone'");
    if ($phoneColRes && $pRow = mysqli_fetch_assoc($phoneColRes)) {
        if (stripos($pRow['Type'], 'int') !== false) {
            mysqli_query($conn, "ALTER TABLE users MODIFY phone VARCHAR(30) NOT NULL");
        }
    }

    // Auto-check/add card and category columns
    $colCard = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'card'");
    if (!$colCard || mysqli_num_rows($colCard) == 0) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN card VARCHAR(50) DEFAULT 'Diamond' AFTER area");
        mysqli_query($conn, "UPDATE users SET card = 'Diamond' WHERE card IS NULL OR card = ''");
    }
    $colCategory = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'category'");
    if (!$colCategory || mysqli_num_rows($colCategory) == 0) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN category VARCHAR(50) DEFAULT 'B' AFTER card");
        mysqli_query($conn, "UPDATE users SET category = 'B' WHERE category IS NULL OR category = ''");
    }

    $userId = $_POST['id']; // the user being edited
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $card = $_POST['card'] ?? 'Diamond';
    $category = $_POST['category'] ?? 'B';
    $role = $_POST['type'];
    $area = $_POST['area'] ?? '';

    // -------------------------------
    // 1️⃣ Generate base username
    // Remove non-alphanumeric chars if needed
    $cleanFirst = preg_replace("/[^a-zA-Z0-9]/", "", $firstName);
    $cleanLast = preg_replace("/[^a-zA-Z0-9]/", "", $lastName);

    $baseUsername = ucfirst($cleanFirst) . ucfirst($cleanLast); // e.g., AhsanImran
    $username = $baseUsername;

    // -------------------------------
    // 2️⃣ Check if username exists (exclude current user)
    $count = 1;
    while (true) {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->bind_param("si", $username, $userId);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows == 0) {
            break; // username is unique ✅
        }

        // Append increment
        $username = $baseUsername . $count; // e.g., AhsanImran1
        $count++;
    }

    // Check if status column exists
    $hasStatus = false;
    $statusCheck = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'status'");
    if ($statusCheck && mysqli_num_rows($statusCheck) > 0) {
        $hasStatus = true;
    }
    $status = $_POST['status'] ?? 'Active';

    // -------------------------------
    // 3️⃣ Update user record
    if ($hasStatus) {
        $stmt = $conn->prepare("
            UPDATE users SET
                firstName = ?, lastName = ?, username = ?, email = ?, phone = ?, card = ?, category = ?, role = ?, area = ?, status = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssssssssssi",
            $firstName,
            $lastName,
            $username,
            $email,
            $phone,
            $card,
            $category,
            $role,
            $area,
            $status,
            $userId
        );
    } else {
        $stmt = $conn->prepare("
            UPDATE users SET
                firstName = ?, lastName = ?, username = ?, email = ?, phone = ?, card = ?, category = ?, role = ?, area = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "sssssssssi",
            $firstName,
            $lastName,
            $username,
            $email,
            $phone,
            $card,
            $category,
            $role,
            $area,
            $userId
        );
    }

if ($stmt->execute()) {
    // ✅ Send a small modal HTML back
    echo '
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Success</h5>
                </div>
                <div class="modal-body">
                    <p>User updated successfully!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    ';
} else {
    echo '
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Error</h5>
                </div>
                <div class="modal-body">
                    <p>❌ Error: ' . $stmt->error . '</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    ';
}
    $stmt->close();
    $conn->close();
}
?>