<?php
include "connection.php";

header('Content-Type: application/json');

// Auto-heal: update users whose area is stored as a numeric ID to the actual areaName
$healQuery = "UPDATE users u JOIN dars_areas d ON u.area = CAST(d.id AS CHAR) SET u.area = d.areaName WHERE u.area REGEXP '^[0-9]+$'";
@mysqli_query($conn, $healQuery);

// Fetch areas map to resolve any remaining numeric IDs
$areasMap = [];
$areasRes = mysqli_query($conn, "SELECT id, areaName FROM dars_areas");
if ($areasRes) {
    while ($aRow = mysqli_fetch_assoc($areasRes)) {
        $areasMap[(string)$aRow['id']] = $aRow['areaName'];
    }
}

$users = [];

$query = "SELECT * FROM users ORDER BY id DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(["error" => mysqli_error($conn)]);
    exit;
}

while ($row = mysqli_fetch_assoc($result)) {
    if (!empty($row['area']) && isset($areasMap[(string)$row['area']])) {
        $row['areaName'] = $areasMap[(string)$row['area']];
        $row['area'] = $areasMap[(string)$row['area']];
    } else {
        $row['areaName'] = $row['area'] ?? '';
    }
    $users[] = $row;
}

echo json_encode($users);
?>