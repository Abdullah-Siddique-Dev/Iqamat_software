<?php
include "connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $userId = $_POST['id']; // the user being edited
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
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

    // -------------------------------
    // 3️⃣ Update user record
    $stmt = $conn->prepare("
        UPDATE users SET
            firstName = ?, lastName = ?, username = ?, email = ?, phone = ?, age = ?, gender = ?, role = ?, area = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "sssssisssi",
        $firstName,
        $lastName,
        $username,
        $email,
        $phone,
        $age,
        $gender,
        $role,
        $area,
        $userId
    );

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