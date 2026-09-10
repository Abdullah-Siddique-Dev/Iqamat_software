<?php
/**
 * Migration Script: Fix 'phone' column type in 'users' table
 * Changes phone from INT(20) to VARCHAR(30) to prevent truncation to 2147483647
 */

include "connection.php";

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>Fix Phone Column</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; background: #0f172a; color: #f8fafc; }
        .card { background: #1e293b; border: 1px solid #334155; padding: 25px; border-radius: 8px; margin-bottom: 20px; }
        .success { background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80; padding: 20px; border-radius: 8px; }
        .error { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; padding: 20px; border-radius: 8px; }
        .info { background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.3); color: #38bdf8; padding: 20px; border-radius: 8px; }
        code { background: #0f172a; padding: 3px 8px; border-radius: 4px; color: #38bdf8; font-family: monospace; }
        a.btn { display: inline-block; background: #0284c7; color: #ffffff; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-top: 15px; }
        a.btn:hover { background: #0369a1; }
    </style>
</head>
<body>";

echo "<h1>Database Migration: Fix Phone Column</h1>";
echo "<hr style='border-color: #334155; margin-bottom: 25px;'>";

$result = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");

if ($result && $row = $result->fetch_assoc()) {
    $currentType = strtolower($row['Type']);
    
    if (strpos($currentType, 'varchar') !== false) {
        echo "<div class='info'>";
        echo "<h2>✅ Column is Already Correct!</h2>";
        echo "<p>The <code>phone</code> column in the <code>users</code> table is currently: <strong>" . htmlspecialchars($row['Type']) . "</strong>.</p>";
        echo "<p>It is already set to text/varchar, so full phone numbers (e.g. <code>03001234567</code> or <code>+92...</code>) are saved without truncation.</p>";
        echo "</div>";
    } else {
        echo "<div class='card'>";
        echo "<p>Current type detected: <code>" . htmlspecialchars($row['Type']) . "</code> (32-bit INT limit 2,147,483,647 causes number truncation).</p>";
        echo "<p>Modifying column to <code>VARCHAR(30)</code>...</p>";
        echo "</div>";

        $sql = "ALTER TABLE users MODIFY phone VARCHAR(30) NOT NULL";
        
        if ($conn->query($sql) === TRUE) {
            echo "<div class='success'>";
            echo "<h2>✅ Success! Phone Column Fixed!</h2>";
            echo "<p>The <code>phone</code> column has been altered to <code>VARCHAR(30) NOT NULL</code>.</p>";
            echo "<p>Phone numbers will no longer be capped at <strong>2147483647</strong>!</p>";
            echo "<ul>";
            echo "<li><strong>New Type:</strong> VARCHAR(30)</li>";
            echo "<li><strong>Supports:</strong> Leading zeroes (e.g. 0300...) and full country codes</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "<h2>❌ Error</h2>";
            echo "<p>Failed to alter column: " . htmlspecialchars($conn->error) . "</p>";
            echo "</div>";
        }
    }
} else {
    echo "<div class='error'>";
    echo "<h2>❌ Error</h2>";
    echo "<p>Could not inspect <code>phone</code> column in <code>users</code> table.</p>";
    echo "</div>";
}

// Check card and category columns
echo "<h2 style='margin-top: 30px;'>Card & Category Columns Check</h2>";
$colCard = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'card'");
if (!$colCard || mysqli_num_rows($colCard) == 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN card VARCHAR(50) DEFAULT 'Diamond' AFTER area")) {
        mysqli_query($conn, "UPDATE users SET card = 'Diamond' WHERE card IS NULL OR card = ''");
        echo "<div class='success'><p>✅ Created <code>card</code> column (VARCHAR(50) DEFAULT 'Diamond').</p></div>";
    } else {
        echo "<div class='error'><p>❌ Failed to add <code>card</code> column: " . htmlspecialchars(mysqli_error($conn)) . "</p></div>";
    }
} else {
    echo "<div class='info'><p>✅ <code>card</code> column already exists.</p></div>";
}

$colCategory = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'category'");
if (!$colCategory || mysqli_num_rows($colCategory) == 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN category VARCHAR(50) DEFAULT 'B' AFTER card")) {
        mysqli_query($conn, "UPDATE users SET category = 'B' WHERE category IS NULL OR category = ''");
        echo "<div class='success'><p>✅ Created <code>category</code> column (VARCHAR(50) DEFAULT 'B').</p></div>";
    } else {
        echo "<div class='error'><p>❌ Failed to add <code>category</code> column: " . htmlspecialchars(mysqli_error($conn)) . "</p></div>";
    }
} else {
    echo "<div class='info'><p>✅ <code>category</code> column already exists.</p></div>";
}

$conn->close();

echo "<p><a href='registeredUsers.php' class='btn'>← Return to User Management</a></p>";
echo "</body></html>";
?>
