<?php
require_once __DIR__ . "/connection.php";

header('Content-Type: application/json');

if ($conn->connect_error) {
    echo json_encode(["error" => "DB connection failed"]);
    exit();
}

$result = $conn->query("
    SELECT
        r.id,
        r.title,
        r.author_id,
        r.research_type,
        r.pdf_path,
        r.created_at,
        CONCAT(u.firstName, ' ', u.lastName) AS author_name
    FROM research r
    LEFT JOIN users u ON u.id = r.author_id
    ORDER BY r.created_at DESC
");

if (!$result) {
    echo json_encode(["error" => $conn->error]);
    exit();
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);