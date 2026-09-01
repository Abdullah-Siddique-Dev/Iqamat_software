<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "connection.php";
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}
header('Content-Type: application/json');

$area_id = intval($_GET['area_id'] ?? 0);
$rawDate = trim($_GET['date']      ?? '');

if (!$area_id) {
    echo json_encode([]);
    exit();
}

// Fetch corresponding areaName for area_id to support string area names stored in users table
$areaName = '';
$areaRes = $conn->query("SELECT areaName FROM dars_areas WHERE id = $area_id LIMIT 1");
if ($areaRes && $row = $areaRes->fetch_assoc()) {
    $areaName = trim($row['areaName']);
}
$areaNameLower = strtolower($areaName);

/*
 * Standardize date format to YYYY-MM-DD
 */
$date = '';
if ($rawDate) {
    $timestamp = strtotime($rawDate);
    if ($timestamp !== false) {
        $date = date('Y-m-d', $timestamp);
    }
}
$useDate = !empty($date);
$areaStr = (string)$area_id;

if ($useDate) {
    $stmt = $conn->prepare("
        SELECT
            u.id,
            u.firstName,
            u.lastName,
            u.username,
            u.email,
            u.phone,
            u.role,
            COALESCE(da.attendance, 'Absent') AS saved_attendance,
            COALESCE(da.timing,     'OnTime') AS saved_timing
        FROM users u
        LEFT JOIN dars_attendance da
               ON da.user_id  = u.id
              AND (CAST(da.area_id AS UNSIGNED) = ? OR da.area_id = ? OR (? != '' AND LOWER(TRIM(da.area_id)) = ?))
              AND da.dateTime = ?
        WHERE (CAST(u.area AS UNSIGNED) = ? OR u.area = ? OR (? != '' AND LOWER(TRIM(u.area)) = ?))
        ORDER BY u.firstName ASC, u.lastName ASC
    ");
    if ($stmt) {
        $stmt->bind_param("issssisss", $area_id, $areaStr, $areaNameLower, $areaNameLower, $date, $area_id, $areaStr, $areaNameLower, $areaNameLower);
    }
} else {
    $stmt = $conn->prepare("
        SELECT
            u.id,
            u.firstName,
            u.lastName,
            u.username,
            u.email,
            u.phone,
            u.role,
            'Absent' AS saved_attendance,
            'OnTime' AS saved_timing
        FROM users u
        WHERE (CAST(u.area AS UNSIGNED) = ? OR u.area = ? OR (? != '' AND LOWER(TRIM(u.area)) = ?))
        ORDER BY u.firstName ASC, u.lastName ASC
    ");
    if ($stmt) {
        $stmt->bind_param("isss", $area_id, $areaStr, $areaNameLower, $areaNameLower);
    }
}

if (!$stmt || !$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => $stmt ? $stmt->error : $conn->error]);
    exit();
}

$result  = $stmt->get_result();
$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($members);
?>