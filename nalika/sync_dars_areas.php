<?php
/**
 * Dars Areas Database Sync Tool
 * Visit this file in your browser to sync the 5 Dars Areas into your database.
 */
require_once __DIR__ . '/connection.php';

if (!$conn || $conn->connect_error) {
    die("Database connection failed: " . ($conn ? $conn->connect_error : "Check connection settings"));
}

// Disable foreign key checks and clean dars_areas table
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("DELETE FROM dars_areas");
$conn->query("ALTER TABLE dars_areas AUTO_INCREMENT = 1");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// The exact 5 Dars Areas
$areas = [
    ["areaName" => "Gulshan Colony",  "darsType" => "Weekly", "dayTime" => "Sunday 2:00 PM",   "location" => "Gulshan Colony"],
    ["areaName" => "PM Colony",       "darsType" => "Weekly", "dayTime" => "Friday 5:00 PM",   "location" => "PM Colony"],
    ["areaName" => "Asifabad Colony", "darsType" => "Weekly", "dayTime" => "Thursday 7:00 PM", "location" => "Asifabad Colony"],
    ["areaName" => "Anwar Chowk",     "darsType" => "Weekly", "dayTime" => "Wednesday 7:15 PM","location" => "Anwar Chowk"],
    ["areaName" => "Rawalpindi",      "darsType" => "Weekly", "dayTime" => "Saturday 6:00 PM", "location" => "Rawalpindi"]
];

$stmt = $conn->prepare("INSERT INTO dars_areas (areaName, darsType, dayTime, location) VALUES (?, ?, ?, ?)");
foreach ($areas as $a) {
    $stmt->bind_param("ssss", $a['areaName'], $a['darsType'], $a['dayTime'], $a['location']);
    $stmt->execute();
}
$stmt->close();

$res = $conn->query("SELECT id, areaName, darsType, dayTime, location FROM dars_areas ORDER BY id ASC");
$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dars Areas Sync Success</title>
    <style>
        body { background: #152036; color: #fff; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #1b2a47; border: 1px solid #253a5c; border-radius: 12px; padding: 32px; max-width: 550px; width: 100%; box-shadow: 0 10px 30px rgba(0,0,0,0.3); text-align: center; }
        h2 { color: #03a9f4; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; text-align: left; }
        th, td { padding: 10px 12px; border-bottom: 1px solid #253a5c; font-size: 14px; }
        th { color: #8a9bb5; }
        .btn { display: inline-block; margin-top: 24px; padding: 10px 24px; background: #03a9f4; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 500; }
        .btn:hover { background: #0288d1; }
    </style>
</head>
<body>
    <div class="card">
        <h2>✓ Dars Areas Synced Successfully!</h2>
        <p style="color: #8a9bb5; font-size: 14px;">The following 5 Dars Areas are now active in your database:</p>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Dars Area Name</th>
                    <th>Type</th>
                    <th>Timing</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td style="font-weight: 600; color: #fff;"><?= htmlspecialchars($r['areaName']) ?></td>
                    <td><?= htmlspecialchars($r['darsType']) ?></td>
                    <td style="color: #8a9bb5;"><?= htmlspecialchars($r['dayTime']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="darsAreasInfo.php" class="btn">Go to Dars Areas Module</a>
    </div>
</body>
</html>
