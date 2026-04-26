<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT email FROM admins LIMIT 10");
while($row = $stmt->fetch()) {
    echo "Admin: " . $row['email'] . "\n";
}
