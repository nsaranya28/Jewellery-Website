<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE coupons ADD COLUMN description TEXT AFTER code");
    echo "✅ coupons.description column added.\n";
} catch (Exception $e) {
    echo "ℹ️ coupons.description: " . $e->getMessage() . "\n";
}

// Add some sample data if it's empty or update existing
$pdo->exec("UPDATE coupons SET description = 'Get an instant discount on all gold jewellery collections. Limited time offer!' WHERE description IS NULL OR description = ''");

echo "Migration complete!";
